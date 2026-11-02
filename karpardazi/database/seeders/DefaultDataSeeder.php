<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class DefaultDataSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->settings() as $key => $value) {
            Setting::put($key, $value);
        }

        foreach ($this->branches() as [$name, $code, $isDefault]) {
            Branch::firstOrCreate(
                ['name' => $name],
                ['code' => $code, 'is_default' => $isDefault]
            );
        }
    }

    public static function settings(): array
    {
        return [
            'org_name' => 'شرکت انبارهای عمومی و خدمات گمرکی ایران',
            'complex_name' => 'مجتمع خراسان',
            'national_id' => '10100351070',
            'economic_code' => '411111361641',
            'form_code' => 'GEN/FI/MH/01',
            'form_revision_date' => '1398/04/26',
            'number_pattern' => '{yy}-{serial:3}',
        ];
    }

    public static function branches(): array
    {
        return [
            ['شهید کاوه', '5', true],
            ['مطهری', null, false],
            ['سرخس', null, false],
            ['لطف‌آباد', null, false],
            ['نیشابور', null, false],
            ['ماهیرود', null, false],
            ['دوغارون', null, false],
            ['بجنورد', null, false],
            ['بیرجند', null, false],
            ['باجگیران', null, false],
        ];
    }
}
