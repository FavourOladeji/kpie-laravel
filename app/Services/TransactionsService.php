<?php

namespace App\Services;

use App\DataTransferObjects\TransactionDto;
use App\Exceptions\DuplicateTransactionException;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class TransactionsService {
    public function logTransaction(TransactionDto $transactionDto)
    {
        $durationForDuplicates = 60;
        $oneMinuteAgo = now()->subSeconds($durationForDuplicates);

        DB::transaction(function () use ($transactionDto, $oneMinuteAgo, $durationForDuplicates) {

            // Check for duplicate transaction within the last 60 seconds
            $isDuplicate = Transaction::where('user_id', $transactionDto->userId)
                ->where('amount', $transactionDto->amount)
                ->where('currency_type', $transactionDto->currencyType)
                ->where('created_at', '>=', $oneMinuteAgo)
                ->lockForUpdate()  // Apply row-level lock
                ->exists();

            if ($isDuplicate) {
                throw new DuplicateTransactionException("Possible duplicate transaction detected. Please try again after {$durationForDuplicates} seconds ");
            }

            // Create the transaction if no duplicate exists
            Transaction::create([
                'reference_number' => $transactionDto->referenceNumber,
                'user_id' => $transactionDto->userId,
                'amount' => $transactionDto->amount,
                'currency_type' => $transactionDto->currencyType,
            ]);

        });
    }

    public function generateReferenceNumber() {
        $timestamp = now()->format('YmdHis');
        $randomString = Str::random(6);
        return $randomString . $timestamp;
    }
}
