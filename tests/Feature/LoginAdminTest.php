<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/pesanan')->assertRedirect('/admin/login');
    }

    public function test_admin_bisa_login_dengan_email(): void
    {
        $admin = User::factory()->create(['password' => 'rahasia123']);

        $this->post('/admin/login', ['login' => $admin->email, 'password' => 'rahasia123'])
            ->assertRedirect('/admin/pesanan');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_bisa_login_dengan_username(): void
    {
        $admin = User::factory()->create(['username' => 'admin10', 'password' => 'rahasia123']);

        $this->post('/admin/login', ['login' => 'admin10', 'password' => 'rahasia123'])
            ->assertRedirect('/admin/pesanan');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_login_gagal_dengan_password_salah(): void
    {
        $admin = User::factory()->create();

        $this->post('/admin/login', ['login' => $admin->email, 'password' => 'salah'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_login_dikunci_setelah_5_kali_gagal(): void
    {
        $admin = User::factory()->create(['password' => 'rahasia123']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', ['login' => $admin->email, 'password' => 'salah']);
        }

        // Percobaan ke-6 ditolak walaupun password-nya benar.
        $this->post('/admin/login', ['login' => $admin->email, 'password' => 'rahasia123'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }
}
