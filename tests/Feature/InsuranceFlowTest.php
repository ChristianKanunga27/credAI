<?php

test('individual users can register with the insurance journey', function () {
    $response = $this->post('/register', [
        'name' => 'Amina Mjanja',
        'email' => 'amina@example.com',
        'role' => 'individual',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertDatabaseHas('users', [
        'email' => 'amina@example.com',
        'role' => 'individual',
    ]);
});

test('insurance quotes can be generated from a sim balance', function () {
    $response = $this->post('/insurance/quote', [
        'name' => 'Amina Mjanja',
        'phone' => '+255712345678',
        'sim_balance' => 250000,
        'coverage_goal' => 'family_protection',
    ]);

    $response->assertOk();
    $response->assertJsonPath('status', 'quote_ready');
    $response->assertJsonPath('monthly_premium', 3500);
});
