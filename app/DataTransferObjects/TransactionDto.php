<?php

namespace App\DataTransferObjects;

use App\Enums\CurrencyType;

class TransactionDto {

    public function __construct(
        public readonly int $userId,
        public readonly string $referenceNumber,
        public readonly int $amount,
        public readonly CurrencyType $currencyType
    )
    {

    }

    public static function fromStoreTransactionRequest(array $validated)
    {
        return new self (
            userId: $validated['user_id'],
            referenceNumber: $validated['reference_number'],
            amount: $validated['amount'],
            currencyType: CurrencyType::tryFrom($validated['currency_type'])
        );
    }


}
