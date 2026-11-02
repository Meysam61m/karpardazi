<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = ['name', 'code', 'is_default'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function documents()
    {
        return $this->hasMany(ExpenseDocument::class);
    }

    public static function default(): ?self
    {
        return static::where('is_default', true)->first() ?? static::orderBy('id')->first();
    }
}
