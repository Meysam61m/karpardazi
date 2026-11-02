<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseDocument extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_FINAL = 'final';

    protected $fillable = [
        'branch_id',
        'user_id',
        'karpardaz_name',
        'document_number',
        'serial',
        'fiscal_year',
        'document_date',
        'tankhah_deposit_amount',
        'tankhah_deposit_date',
        'description',
        'attachments_pages',
        'status',
        'finalized_at',
        'total_amount',
        'total_tax',
        'total_insurance',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'tankhah_deposit_amount' => 'integer',
            'tankhah_deposit_date' => 'date',
            'finalized_at' => 'datetime',
            'attachments_pages' => 'integer',
            'total_amount' => 'integer',
            'total_tax' => 'integer',
            'total_insurance' => 'integer',
        ];
    }

    public function items()
    {
        return $this->hasMany(ExpenseItem::class)->orderBy('row_number');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isFinal(): bool
    {
        return $this->status === self::STATUS_FINAL;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /** جمع کل سند (مبلغ + مالیات + بیمه) */
    public function grandTotal(): int
    {
        return $this->total_amount + $this->total_tax + $this->total_insurance;
    }

    /** محاسبه مجدد جمع‌ها از روی اقلام */
    public function recalcTotals(): void
    {
        $this->total_amount = (int) $this->items()->sum('amount');
        $this->total_tax = (int) $this->items()->sum('tax');
        $this->total_insurance = (int) $this->items()->sum('insurance');
    }

    public function canDelete(): bool
    {
        return $this->isDraft();
    }
}
