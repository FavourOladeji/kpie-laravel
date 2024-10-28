<?php

namespace App\Models;

use App\Enums\CurrencyType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'currency_type',
        'user_id',
        'reference_number',
        'amount'
    ];

    protected $casts = [
        'currency_type' => CurrencyType::class
    ];
}
