<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_redirects_to_admin_login(): void
    {
        $response = $this->get('/login');

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('admin.login'));

        $response->assertOk();
    }

    public function test_admin_can_authenticate_with_otp(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);

        // Step 1: Submit credentials
        $response = $this->post(route('admin.login.submit'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.otp.verify.form'));
        $this->assertNotNull(session('admin_otp_user_id'));

        // Step 2: Verify OTP
        $user->refresh();
        $response = $this->post(route('admin.otp.verify'), [
            'otp' => $user->otp_code,
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_admin_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post(route('admin.login.submit'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_cannot_authenticate_with_invalid_otp(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);

        // Submit credentials first
        $this->post(route('admin.login.submit'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Try wrong OTP
        $response = $this->post(route('admin.otp.verify'), [
            'otp' => '000000',
        ]);

        $response->assertSessionHasErrors('otp');
        $this->assertGuest();
    }

    public function test_dashboard_redirects_based_on_role(): void
    {
        $user = User::factory()->create([
            'role' => 'reseller',
            'status' => 'active',
        ]);

        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response->assertRedirect(route('reseller.dashboard'));
    }

    public function test_admin_can_logout(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($user);

        $response = $this->post(route('admin.logout'));

        $response->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }
}
