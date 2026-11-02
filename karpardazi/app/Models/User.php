<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_KARPARDAZ = 'karpardaz';

    protected $fillable = [
        'name',
        'username',
        'password',
        'role',
        'is_active',
        'tankhah_amount',
        'tankhah_charged_on',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'tankhah_amount' => 'integer',
            'tankhah_charged_on' => 'date',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isKarpardaz(): bool
    {
        return $this->role === self::ROLE_KARPARDAZ;
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class)->orderBy('branches.name');
    }

    public function canAccessBranch(int $branchId): bool
    {
        return $this->isAdmin() || $this->branches()->where('branches.id', $branchId)->exists();
    }

    public function documents()
    {
        return $this->hasMany(ExpenseDocument::class);
    }

    /** مجموع هزینه‌های قطعی ثبت‌شده توسط این کارپرداز از تاریخ شارژ تنخواه */
    public function tankhahSpent(): int
    {
        if (! $this->tankhah_amount) {
            return 0;
        }

        $query = $this->documents()->where('status', 'final');
        if ($this->tankhah_charged_on) {
            $query->whereDate('document_date', '>=', $this->tankhah_charged_on);
        }

        return (int) $query
            ->selectRaw('COALESCE(SUM(total_amount + total_tax + total_insurance), 0) AS spent')
            ->value('spent');
    }

    /** مانده تنخواه (null اگر تنخواه تعریف نشده) */
    public function tankhahRemaining(): ?int
    {
        if (! $this->tankhah_amount) {
            return null;
        }

        return $this->tankhah_amount - $this->tankhahSpent();
    }
}
