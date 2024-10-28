<?php

use App\DataTransferObjects\TransactionDto;
use App\Enums\CurrencyType;
use App\Exceptions\DuplicateTransactionException;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionsService;

/**
 * This unit test is for the logTransaction() function in TransactionController
 */

it('throws a DuplicateTransactionException when a duplicate transaction is logged', function () {
    $user = actingAsUser();
    $payload = [
        'amount' => 100.0,
        'currency_type' => CurrencyType::Fiat->value,
    ];

    // Log the first transaction
    $this->post('/transactions', $payload);
    $this->assertEquals(1, Transaction::where('user_id', $user->id)->count());

    $transactionController = app()->make(TransactionsService::class);
    $payload['reference_number'] = Transaction::generateReferenceNumber();
    $payload['user_id'] = $user->id;
    $transactionController->logTransaction(TransactionDto::fromStoreTransactionRequest($payload));

})->throws(DuplicateTransactionException::class);

it('handles high volumes of transactions efficiently', function () {

    $payload = [
        'amount' => 100.0,
        'currency_type' => CurrencyType::Fiat->value,
    ];

    $startTime = microtime(true);

    // Simulate logging multiple transactions
    for ($i = 0; $i < 1000; $i++) {

        $user = User::factory()->create(); // Simulate an authenticated user
        $transactionController = app()->make(TransactionsService::class);
        $payload['reference_number'] = Transaction::generateReferenceNumber();
        $payload['user_id'] = $user->id;
        $transactionController->logTransaction(TransactionDto::fromStoreTransactionRequest($payload));

    }

    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;

    expect($executionTime)->toBeLessThan(120);
});
