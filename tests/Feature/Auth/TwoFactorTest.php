<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'reseller']);
        Role::create(['name' => 'client']);

        $this->user = User::factory()->client()->create();
        $this->user->assignRole('client');
    }

    public function test_user_can_view_setup_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('two-factor.setup'));

        $response->assertOk();
        $response->assertSee('Scan QR Code');
    }

    public function test_setup_generates_secret(): void
    {
        $this->actingAs($this->user)
            ->get(route('two-factor.setup'));

        $this->user->refresh();
        $this->assertNotNull($this->user->two_fa_secret);
    }

    public function test_confirm_with_valid_code_enables_2fa(): void
    {
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        $this->user->update(['two_fa_secret' => encrypt($secret)]);

        $validCode = $google2fa->getCurrentOtp($secret);

        $response = $this->actingAs($this->user)
            ->post(route('two-factor.confirm'), ['code' => $validCode]);

        $response->assertOk();
        $response->assertSee('Save these recovery codes');

        $this->user->refresh();
        $this->assertTrue($this->user->two_fa_enabled);
        $this->assertNotNull($this->user->two_fa_confirmed_at);
        $this->assertCount(8, $this->user->two_fa_recovery_codes);
    }

    public function test_confirm_with_invalid_code_fails(): void
    {
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        $this->user->update(['two_fa_secret' => encrypt($secret)]);

        $response = $this->actingAs($this->user)
            ->post(route('two-factor.confirm'), ['code' => '000000']);

        $response->assertSessionHasErrors('code');
    }

    public function test_status_page_shows_enabled(): void
    {
        $this->user->update([
            'two_fa_enabled' => true,
            'two_fa_confirmed_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('two-factor.status'));

        $response->assertOk();
        $response->assertSee('Two-factor authentication is enabled');
    }

    public function test_disable_requires_password(): void
    {
        $this->user->update([
            'two_fa_enabled' => true,
            'two_fa_secret' => encrypt('testsecret'),
            'two_fa_confirmed_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('two-factor.disable'), ['password' => 'wrong']);

        $response->assertSessionHasErrors('password');

        $this->user->refresh();
        $this->assertTrue($this->user->two_fa_enabled);
    }

    public function test_disable_with_correct_password(): void
    {
        $this->user->update([
            'two_fa_enabled' => true,
            'two_fa_secret' => encrypt('testsecret'),
            'two_fa_confirmed_at' => now(),
            'two_fa_recovery_codes' => ['code1', 'code2'],
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('two-factor.disable'), ['password' => 'password']);

        $response->assertRedirect(route('two-factor.status'));

        $this->user->refresh();
        $this->assertFalse($this->user->two_fa_enabled);
        $this->assertNull($this->user->two_fa_secret);
    }

    public function test_login_redirects_to_challenge_when_2fa_enabled(): void
    {
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        $this->user->update([
            'two_fa_enabled' => true,
            'two_fa_secret' => encrypt($secret),
            'two_fa_confirmed_at' => now(),
        ]);

        // Livewire login posts via wire:submit, but we can test the challenge flow
        // by simulating the session state
        $response = $this->withSession(['2fa:user_id' => $this->user->id])
            ->get(route('two-factor.challenge'));

        $response->assertOk();
        $response->assertSee('Two-Factor Authentication');
    }

    public function test_challenge_redirects_to_login_without_session(): void
    {
        $response = $this->get(route('two-factor.challenge'));

        $response->assertRedirect(route('login'));
    }

    public function test_verify_with_valid_totp(): void
    {
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        $this->user->update([
            'two_fa_enabled' => true,
            'two_fa_secret' => encrypt($secret),
            'two_fa_confirmed_at' => now(),
        ]);

        $validCode = $google2fa->getCurrentOtp($secret);

        $response = $this->withSession(['2fa:user_id' => $this->user->id])
            ->post(route('two-factor.verify'), ['code' => $validCode]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_verify_with_recovery_code(): void
    {
        $recoveryCode = 'abc123test';

        $this->user->update([
            'two_fa_enabled' => true,
            'two_fa_secret' => encrypt('testsecret'),
            'two_fa_confirmed_at' => now(),
            'two_fa_recovery_codes' => [bcrypt($recoveryCode), bcrypt('other_code')],
        ]);

        $response = $this->withSession(['2fa:user_id' => $this->user->id])
            ->post(route('two-factor.verify'), ['code' => $recoveryCode]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->user);

        // Recovery code should be consumed
        $this->user->refresh();
        $this->assertCount(1, $this->user->two_fa_recovery_codes);
    }

    public function test_verify_with_invalid_code_fails(): void
    {
        $this->user->update([
            'two_fa_enabled' => true,
            'two_fa_secret' => encrypt('testsecret'),
            'two_fa_confirmed_at' => now(),
            'two_fa_recovery_codes' => [],
        ]);

        $response = $this->withSession(['2fa:user_id' => $this->user->id])
            ->post(route('two-factor.verify'), ['code' => 'invalid']);

        $response->assertSessionHasErrors('code');
    }
}
