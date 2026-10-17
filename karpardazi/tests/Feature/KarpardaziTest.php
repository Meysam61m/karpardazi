<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\ExpenseDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KarpardaziTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function admin(): User
    {
        return User::create([
            'name' => 'مدیر', 'username' => 'admin', 'password' => 'secret123',
            'role' => 'admin', 'is_active' => true,
        ]);
    }

    private function karpardaz(): User
    {
        return User::create([
            'name' => 'کارپرداز', 'username' => 'kar', 'password' => 'secret123',
            'role' => 'karpardaz', 'is_active' => true,
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'branch_id' => Branch::first()->id,
            'document_date' => '1405/07/20',
            'karpardaz_name' => 'میثم انجیدانی',
            'attachments_pages' => 12,
            'description' => null,
            'items' => [
                ['expense_date' => '1405/07/12', 'description' => 'خرید لوازم اداری', 'amount' => '4,500,000', 'tax' => '450,000', 'insurance' => ''],
                ['expense_date' => '۱۴۰۵/۰۷/۱۵', 'description' => 'هزینه حمل', 'amount' => '۲,۸۰۰,۰۰۰', 'tax' => '', 'insurance' => '120000'],
            ],
            'action' => 'save_draft',
        ], $overrides);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/documents')->assertRedirect('/login');
    }

    public function test_user_can_login_and_see_dashboard(): void
    {
        $admin = $this->admin();
        $this->post('/login', ['username' => 'admin', 'password' => 'secret123'])
            ->assertRedirect('/');
        $this->get('/')->assertOk()->assertSee('داشبورد');
    }

    public function test_draft_gets_no_number_and_final_gets_sequential_number(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/documents', $this->validPayload())
            ->assertRedirect();
        $draft = ExpenseDocument::first();
        $this->assertSame('draft', $draft->status);
        $this->assertNull($draft->document_number);
        $this->assertSame(7300000, $draft->total_amount);
        $this->assertSame(450000, $draft->total_tax);
        $this->assertSame(120000, $draft->total_insurance);

        $this->actingAs($admin)->post('/documents', $this->validPayload(['action' => 'save_final']))
            ->assertRedirect();
        $final = ExpenseDocument::find(2);
        $this->assertSame('final', $final->status);
        $this->assertSame('405-001', $final->document_number);
        $this->assertSame(1, $final->serial);
        $this->assertSame(1405, $final->fiscal_year);

        $this->actingAs($admin)->post('/documents', $this->validPayload(['action' => 'save_final']));
        $this->assertSame('405-002', ExpenseDocument::find(3)->document_number);
    }

    public function test_number_is_immutable_after_finalize(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/documents', $this->validPayload(['action' => 'save_final']));
        $doc = ExpenseDocument::first();
        $number = $doc->document_number;

        $this->actingAs($admin)->put("/documents/{$doc->id}", $this->validPayload([
            'action' => 'save_final',
            'karpardaz_name' => 'نام دیگر',
        ]))->assertRedirect();

        $doc->refresh();
        $this->assertSame($number, $doc->document_number);
        $this->assertSame('نام دیگر', $doc->karpardaz_name);
        $this->assertTrue(AuditLog::where('event', 'document_update')->exists());
    }

    public function test_draft_can_be_deleted_but_final_cannot(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/documents', $this->validPayload());
        $draft = ExpenseDocument::first();
        $this->actingAs($admin)->delete("/documents/{$draft->id}")->assertRedirect('/documents');
        $this->assertSame(0, ExpenseDocument::count());

        $this->actingAs($admin)->post('/documents', $this->validPayload(['action' => 'save_final']));
        $final = ExpenseDocument::first();
        $this->actingAs($admin)->delete("/documents/{$final->id}")->assertRedirect();
        $this->assertSame(1, ExpenseDocument::count());
    }

    public function test_invalid_jalali_date_is_rejected(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/documents', $this->validPayload(['document_date' => '1405/13/01']))
            ->assertSessionHasErrors('document_date');
    }

    public function test_karpardaz_cannot_access_admin_area(): void
    {
        $kar = $this->karpardaz();
        $this->actingAs($kar)->get('/admin/users')->assertForbidden();
        $this->actingAs($kar)->get('/documents')->assertOk();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $kar = $this->karpardaz();
        $kar->update(['is_active' => false]);
        $this->post('/login', ['username' => 'kar', 'password' => 'secret123'])
            ->assertSessionHasErrors('username');
    }

    public function test_document_number_is_unique_and_searchable(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/documents', $this->validPayload(['action' => 'save_final']));
        $this->actingAs($admin)->get('/documents?q=405-001')->assertOk()->assertSee('405-001');
    }

    public function test_print_page_renders_form_layout(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/documents', $this->validPayload(['action' => 'save_final']));
        $doc = ExpenseDocument::first();

        $response = $this->actingAs($admin)->get("/documents/{$doc->id}/print");
        $response->assertOk()
            ->assertSee('سند هزینه شعبه')
            ->assertSee('جمع کل')
            ->assertSee('امضاء کارپرداز')
            ->assertSee('دستور صدور چک');
    }

    public function test_tankhah_amount_accepts_separators_and_persian_digits(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'کارپرداز دوم',
            'username' => 'kar2',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'karpardaz',
            'is_active' => '1',
            'tankhah_amount' => '۱۰,۰۰۰,۰۰۰',
            'tankhah_charged_on' => '۱۴۰۵/۰۷/۰۱',
        ])->assertSessionHasNoErrors();

        $user = User::where('username', 'kar2')->first();
        $this->assertSame(10000000, $user->tankhah_amount);
        $this->assertNotNull($user->tankhah_charged_on);
    }

    public function test_print_is_blocked_until_tankhah_charge_date_is_set(): void
    {
        $kar = User::create([
            'name' => 'کارپرداز سوم', 'username' => 'kar3', 'password' => 'secret123',
            'role' => 'karpardaz', 'is_active' => true,
            'tankhah_amount' => 5000000, 'tankhah_charged_on' => null,
        ]);

        $this->actingAs($kar)->post('/documents', $this->validPayload([
            'action' => 'save_final',
            'karpardaz_name' => 'کارپرداز سوم',
        ]));
        $doc = ExpenseDocument::first();

        $this->actingAs($kar)->get("/documents/{$doc->id}/print")
            ->assertRedirect(route('documents.show', $doc))
            ->assertSessionHas('error');

        $kar->update(['tankhah_charged_on' => \App\Support\Jalali::toGregorian('1405/07/01')]);

        $this->actingAs($kar)->get("/documents/{$doc->id}/print")
            ->assertOk()
            ->assertSee('سند هزینه شعبه');
    }
}
