<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseItem extends Model
{
    protected $fillable = [
        'expense_document_id',
        'row_number',
        'expense_date',
        'description',
        'amount',
        'tax',
        'insurance',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'integer',
            'tax' => 'integer',
            'insurance' => 'integer',
        ];
    }

    public function document()
    {
        return $this->belongsTo(ExpenseDocument::class);
    }
}
