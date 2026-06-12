<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterUsernameAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_username_lookup_reports_existing_reserved_and_available_values(): void
    {
        $this->seed();

        $this->getJson(route('register.username.lookup', ['username' => 'admin']))
            ->assertOk()
            ->assertJson(['exists' => true]);

        $this->getJson(route('register.username.lookup', ['username' => 'nguyenvana']))
            ->assertOk()
            ->assertJson(['exists' => true]);

        $this->getJson(route('register.username.lookup', ['username' => 'tranvancanh2026']))
            ->assertOk()
            ->assertJson(['exists' => false]);
    }

    public function test_registration_rejects_duplicate_username_even_with_different_casing(): void
    {
        $this->seed();

        $this->from('/register')->post('/register', [
            'username' => 'NGUYENVANA',
            'name' => 'New User',
            'email' => 'fresh@example.com',
            'phone' => '0922222222',
            'password' => 'Password1@',
            'password_confirmation' => 'Password1@',
            'accepted_terms' => '1',
        ])->assertRedirect('/register')
            ->assertSessionHasErrors('username');

        $this->assertSame(1, User::query()->whereRaw('LOWER(username) = ?', ['nguyenvana'])->count());
    }

    public function test_registration_rejects_reserved_username(): void
    {
        $this->seed();

        $this->from('/register')->post('/register', [
            'username' => 'Administrator',
            'name' => 'New User',
            'email' => 'reserved@example.com',
            'phone' => '0922222222',
            'password' => 'Password1@',
            'password_confirmation' => 'Password1@',
            'accepted_terms' => '1',
        ])->assertRedirect('/register')
            ->assertSessionHasErrors('username');
    }

    public function test_registration_stores_username_in_lowercase_when_valid(): void
    {
        $this->seed();

        $this->post('/register', [
            'username' => 'TranVanCanh',
            'name' => 'New User',
            'email' => 'fresh@example.com',
            'phone' => '0922222222',
            'password' => 'Password1@',
            'password_confirmation' => 'Password1@',
            'accepted_terms' => '1',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'username' => 'tranvancanh',
            'email' => 'fresh@example.com',
        ]);
    }

    public function test_registration_rejects_username_with_special_characters_or_diacritics(): void
    {
        $this->seed();

        $this->from('/register')->post('/register', [
            'username' => 'trần-văn',
            'name' => 'New User',
            'email' => 'invalid-username@example.com',
            'phone' => '0933333333',
            'password' => 'Password1@',
            'password_confirmation' => 'Password1@',
            'accepted_terms' => '1',
        ])->assertRedirect('/register')
            ->assertSessionHasErrors('username');
    }
}
