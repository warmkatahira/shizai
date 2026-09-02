<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * must_change_password が立っているあいだは、パスワードを変えるまで
 * 他の画面を開けないこと（App\Http\Middleware\EnsurePasswordChanged）。
 */
class MustChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    private function user(bool $mustChange): User
    {
        return User::create([
            'name' => 'テスト管理者',
            'login_id' => 'tester',
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make('password'),
            'is_active' => true,
            'must_change_password' => $mustChange,
        ]);
    }

    public function test_フラグが立っていると他の画面はパスワード変更へ飛ばされる(): void
    {
        $this->actingAs($this->user(true))
            ->get('/orders')
            ->assertRedirect(route('password.edit'));
    }

    public function test_フラグが立っていてもパスワード変更画面は開ける(): void
    {
        $this->actingAs($this->user(true))
            ->get('/password')
            ->assertOk();
    }

    public function test_パスワードを変更するとフラグが下りて通常どおり使える(): void
    {
        $user = $this->user(true);

        $this->actingAs($user)->put('/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertFalse($user->fresh()->must_change_password);
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));

        $this->actingAs($user->fresh())->get('/orders')->assertOk();
    }

    public function test_フラグが立っていなければ飛ばされない(): void
    {
        $this->actingAs($this->user(false))
            ->get('/orders')
            ->assertOk();
    }
}
