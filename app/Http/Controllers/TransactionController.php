<?php

namespace App\Http\Controllers;

use App\Enums\CurrencyType;
use App\Exceptions\DuplicateTransactionException;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Enum;
use Throwable;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TransactionController extends Controller
{
    public function store(Request $request)
    {
        $transactionData = $request->validate([
            'amount' => ['required', 'numeric'],
            'currency_type' => ['required', new Enum(CurrencyType::class)],
        ]);
        $transactionData['user_id'] = auth()->id();

        $transactionData['reference_number'] = $this->generateReferenceNumber();


        try {
            $this->logTransaction($transactionData);
            return response()->json([
                'message' => 'Transaction successfully logged.',
                'reference_number' => $transactionData['reference_number'],
            ], Response::HTTP_CREATED);

        } catch (DuplicateTransactionException $ex) {
            return response()->json([
                'error' => $ex->getMessage(),
            ], Response::HTTP_CONFLICT);

    } catch (Throwable $th){
            Log::error($th->getMessage(), ['Transaction Creation']);
            return response()->json([
                'error' => "Unable to log transaction due to internal errors"
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    public function logTransaction($transactionData)
    {
        $durationForDuplicates = 60;
        $oneMinuteAgo = Carbon::now()->subSeconds($durationForDuplicates);

        DB::transaction(function () use ($transactionData, $oneMinuteAgo, $durationForDuplicates) {

            // Check for duplicate transaction within the last 60 seconds
            $isDuplicate = Transaction::where('user_id', $transactionData['user_id'])
                ->where('amount', $transactionData['amount'])
                ->where('currency_type', $transactionData['currency_type'])
                ->where('created_at', '>=', $oneMinuteAgo)
                ->lockForUpdate()  // Apply row-level lock
                ->exists();

            if ($isDuplicate) {
                throw new DuplicateTransactionException("Possible duplicate transaction detected. Please try again after {$durationForDuplicates} seconds ");
            }

            // Create the transaction if no duplicate exists
            Transaction::create([
                'reference_number' => $transactionData['reference_number'],
                'user_id' => $transactionData['user_id'],
                'amount' => $transactionData['amount'],
                'currency_type' => $transactionData['currency_type'],
            ]);

        });
    }

    public function generateReferenceNumber() {
        $timestamp = now()->format('YmdHis');
        $randomString = Str::random(6);
        return $randomString . $timestamp;
    }
}
