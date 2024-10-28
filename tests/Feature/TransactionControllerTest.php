<?php

use App\Enums\CurrencyType;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class)->in('Feature');
use function Pest\Laravel\{actingAs, assertDatabaseHas};

// Helper function to log in a user
function actingAsUser() {
    $user = User::factory()->create();
    actingAs($user);
    return $user;
}

it('logs a transaction successfully', function () {
    $user = actingAsUser();

    $payload = [
        'amount' => 100.0,
        'currency_type' => CurrencyType::Fiat->value,
    ];

    $response = $this->post('/transactions', $payload);
    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'reference_number'
        ]);
    assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'amount' => $payload['amount'],
        'currency_type' => $payload['currency_type'],
    ]);

});

it('detects and prevents duplicate transactions within 60 seconds', function () {
    $user = actingAsUser();

    $payload = [
        'amount' => 100.0,
        'currency_type' => CurrencyType::Fiat->value,
    ];

    // Log the first transaction
   $this->post('/transactions', $payload);

    // Attempt to log the same transaction within 60 seconds
    $response = $this->post('/transactions', $payload);

    $response->assertStatus(Response::HTTP_CONFLICT);

    // Ensure only one transaction exists in the database
    $this->assertEquals(1, Transaction::where('user_id', $user->id)->count());
});

it('returns validation errors for invalid payload', function () {
    actingAsUser();

    $payload = [
        'amount' => 'invalid_amount',
        'currency_type' => 'invalid_currency',
    ];

    $response = $this->postJson('/transactions', $payload);

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
             ->assertJsonValidationErrors(['amount', 'currency_type']);
});


