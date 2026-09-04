<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * アカウントごとの「表示するマスタ」（users.visible_masters）。
 *
 * 管理者・総務は常に全マスタを編集できる。それ以外の権限は、
 * オンにしたマスタの一覧を**閲覧だけ**できる（App\Http\Middleware\EnsureMasterVisible）。
 */
class MasterVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, array $visibleMasters = [], bool $withOffice = false, array $editableMasters = []): User
    {
        return User::create([
            'name' => 'テスト',
            'login_id' => 'tester' . User::count(),
            'role' => $role,
            'office_id' => $withOffice ? Office::create(['name' => 'テスト営業所', 'code' => 'TEST'])->id : null,
            'password' => Hash::make('password'),
            'is_active' => true,
            'visible_masters' => $visibleMasters,
            'editable_masters' => $editableMasters,
        ]);
    }

    public function test_管理者と総務はトグルに関係なく全マスタを開ける(): void
    {
        foreach ([User::ROLE_ADMIN, User::ROLE_GENERAL_AFFAIRS] as $role) {
            $user = $this->user($role);

            foreach (array_keys(User::MASTERS) as $master) {
                $this->actingAs($user)->get("/admin/{$master}")->assertOk();
            }
        }
    }

    public function test_営業所ユーザーはオンにしたマスタだけ開ける(): void
    {
        $user = $this->user(User::ROLE_SALES, ['suppliers'], true);

        $this->actingAs($user)->get('/admin/suppliers')->assertOk();
        $this->actingAs($user)->get('/admin/materials')->assertForbidden();
    }

    public function test_トグルが全部オフならマスタは1つも開けない(): void
    {
        $user = $this->user(User::ROLE_SALES, [], true);

        foreach (array_keys(User::MASTERS) as $master) {
            $this->actingAs($user)->get("/admin/{$master}")->assertForbidden();
        }
    }

    public function test_閲覧だけなら登録編集削除とCSVはできない(): void
    {
        $user = $this->user(User::ROLE_SALES, ['suppliers'], true);

        $this->actingAs($user)->get('/admin/suppliers/create')->assertForbidden();
        $this->actingAs($user)->post('/admin/suppliers', ['name' => 'テスト業者'])->assertForbidden();
        $this->actingAs($user)->get('/admin/suppliers-export')->assertForbidden();
        $this->actingAs($user)->post('/admin/suppliers-import')->assertForbidden();
    }

    public function test_一覧に編集のリンクとCSV取り込みは出ない(): void
    {
        $this->actingAs($this->user(User::ROLE_SALES, ['categories'], true))
            ->get('/admin/categories')
            ->assertOk()
            ->assertDontSee('新規カテゴリ')
            ->assertDontSee('CSVから取り込む')
            ->assertDontSee('CSVダウンロード');

        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get('/admin/categories')
            ->assertOk()
            ->assertSee('新規カテゴリ')
            ->assertSee('CSVから取り込む');
    }

    public function test_ユーザー管理はトグルの対象外で管理者だけ(): void
    {
        $this->actingAs($this->user(User::ROLE_SALES, array_keys(User::MASTERS), true))
            ->get('/admin/users')
            ->assertForbidden();

        $this->actingAs($this->user(User::ROLE_GENERAL_AFFAIRS))
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_編集をオンにするとそのマスタだけ登録編集削除とCSVができる(): void
    {
        $user = $this->user(User::ROLE_SALES, [], true, ['suppliers']);

        // 編集できるマスタ
        $this->actingAs($user)->get('/admin/suppliers')->assertOk();
        $this->actingAs($user)->get('/admin/suppliers/create')->assertOk();
        $this->actingAs($user)->get('/admin/suppliers-export')->assertOk();
        $this->actingAs($user)->post('/admin/suppliers', [
            'name' => 'テスト業者',
            'is_active' => '1',
        ])->assertRedirect(route('admin.suppliers.index'));

        // 他のマスタは今までどおり触れない
        $this->actingAs($user)->get('/admin/materials')->assertForbidden();
        $this->actingAs($user)->get('/admin/materials/create')->assertForbidden();
    }

    public function test_編集をオンにすると閲覧もオンになる(): void
    {
        // visible には入れず editable だけ立てても一覧は開ける
        $user = $this->user(User::ROLE_SALES, [], true, ['categories']);

        $this->assertTrue($user->canViewMaster('categories'));
        $this->actingAs($user)->get('/admin/categories')->assertOk();
    }

    public function test_編集できる人には一覧に編集のリンクとCSVが出る(): void
    {
        $this->actingAs($this->user(User::ROLE_SALES, [], true, ['categories']))
            ->get('/admin/categories')
            ->assertOk()
            ->assertSee('新規カテゴリ')
            ->assertSee('CSVから取り込む')
            ->assertSee('CSVダウンロード');
    }

    public function test_ユーザー管理の画面にマスタのトグルが出る(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);

        $this->actingAs($admin)->get('/admin/users/create')
            ->assertOk()
            ->assertSee('マスタの権限')
            ->assertSee('name="visible_masters[suppliers]"', false)
            ->assertSee('name="editable_masters[suppliers]"', false);

        $target = $this->user(User::ROLE_SALES, ['offices'], true);

        $this->actingAs($admin)->get("/admin/users/{$target->id}/edit")
            ->assertOk()
            ->assertSee('マスタの権限');
    }

    public function test_ユーザー管理から閲覧と編集を保存できる(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $office = Office::create(['name' => '保存テスト営業所', 'code' => 'SAVE']);

        $this->actingAs($admin)->post('/admin/users', [
            'name' => '営業所の人',
            'login_id' => 'sales-person',
            'role' => User::ROLE_SALES,
            'office_id' => $office->id,
            'password' => 'password',
            'visible_masters' => ['suppliers' => '1', 'categories' => '1', 'unknown' => '1'],
            // 閲覧を送っていない資材も、編集がオンなら閲覧に入る
            'editable_masters' => ['materials' => '1'],
        ])->assertRedirect(route('admin.users.index'));

        $saved = User::where('login_id', 'sales-person')->first();

        // 知らないキーは捨てる。並びは User::MASTERS の順に揃える
        $this->assertSame(['materials', 'categories', 'suppliers'], $saved->visible_masters);
        $this->assertSame(['materials'], $saved->editable_masters);
    }

    public function test_管理者と総務は送られてこなくても全部オンで保存される(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);

        $this->actingAs($admin)->post('/admin/users', [
            'name' => '総務の人',
            'login_id' => 'affairs-person',
            'role' => User::ROLE_GENERAL_AFFAIRS,
            'password' => 'password',
        ])->assertRedirect(route('admin.users.index'));

        $saved = User::where('login_id', 'affairs-person')->first();

        $this->assertSame(array_keys(User::MASTERS), $saved->visible_masters);
        $this->assertSame(array_keys(User::MASTERS), $saved->editable_masters);
    }
}
