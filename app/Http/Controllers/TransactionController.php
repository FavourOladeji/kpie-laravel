<?php

namespace App\Http\Controllers;

use App\DataTransferObjects\TransactionDto;
use App\Exceptions\DuplicateTransactionException;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Transaction;
use App\Services\TransactionsService;
use Illuminate\Support\Facades\Log;
use Throwable;
use Symfony\Component\HttpFoundation\Response;

class TransactionController extends Controller
{
    public function __construct(protected TransactionsService $transactionsService)
    {

    }

    public function store(StoreTransactionRequest $request)
    {
        $transactionData = $request->validated();
        $transactionData['user_id'] = auth()->id();
        $transactionData['reference_number'] = Transaction::generateReferenceNumber();
        $transactionDto = TransactionDto::fromStoreTransactionRequest($transactionData);

        try {
            $this->transactionsService->logTransaction($transactionDto);
            return response()->json([
                'message' => 'Transaction successfully logged.',
                'reference_number' => $transactionDto->referenceNumber,
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

}
