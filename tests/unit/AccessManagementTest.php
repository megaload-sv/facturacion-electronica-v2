<?php

use App\Libraries\AccessAssignments;
use App\Libraries\AccessControl;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class AccessManagementTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $db = Database::connect('tests');
        foreach ([
            'users' => 'id INTEGER PRIMARY KEY, username TEXT UNIQUE, email TEXT, password TEXT, created_at TEXT, updated_at TEXT',
            'roles' => 'id INTEGER PRIMARY KEY, role_name TEXT UNIQUE, description TEXT',
            'role_user' => 'user_id INTEGER, role_id INTEGER',
            'permissions' => 'id INTEGER PRIMARY KEY, permission_name TEXT, description TEXT',
            'permission_user' => 'user_id INTEGER, permission_id INTEGER',
            'permission_role' => 'role_id INTEGER, permission_id INTEGER',
            'role_menu' => 'role_id INTEGER, menu_item_id INTEGER',
            'menu_items' => 'id INTEGER PRIMARY KEY, name TEXT, url TEXT, parent_id INTEGER, "order" INTEGER, icon TEXT',
        ] as $table => $fields) {
            $db->query('DROP TABLE IF EXISTS ' . $db->prefixTable($table));
            $db->query('CREATE TABLE ' . $db->prefixTable($table) . ' (' . $fields . ')');
        }
        $hash = password_hash('administrator-passphrase', PASSWORD_DEFAULT);
        $db->table('users')->insert(['id' => 1, 'username' => 'administrator', 'email' => 'admin@example.com', 'password' => $hash]);
        $db->table('roles')->insertBatch([['id' => 1, 'role_name' => 'admin', 'description' => 'Administración'], ['id' => 2, 'role_name' => 'operador', 'description' => 'Operaciones']]);
        $db->table('permissions')->insertBatch([
            ['id' => 1, 'permission_name' => 'invoices.view', 'description' => 'Consultar facturas'],
            ['id' => 2, 'permission_name' => 'invoices.process', 'description' => 'Procesar facturas'],
            ['id' => 3, 'permission_name' => 'security.manage', 'description' => 'Administración'],
        ]);
        $db->table('role_user')->insert(['user_id' => 1, 'role_id' => 1]);
        $db->table('permission_role')->insert(['role_id' => 2, 'permission_id' => 1]);
        session()->set(['logged_in' => true, 'user_id' => 1, 'username' => 'administrator', 'authenticated_at' => time(), 'last_activity' => time(), 'credential_version' => hash('sha256', $hash)]);
    }

    private function payload(array $changes = []): array
    {
        $this->withSession();
        return array_replace([csrf_token() => csrf_hash(), 'user_name' => 'nuevo.usuario', 'email' => 'nuevo@example.com', 'password' => 'una-frase-larga-segura', 'password_confirm' => 'una-frase-larga-segura', 'role_ids' => ['2'], 'permission_ids' => ['2']], $changes);
    }

    public function testCreatesAccountWithInheritedAndDirectAccess(): void
    {
        $this->post('/users/store', $this->payload())->assertRedirectTo(site_url('users'));
        $user = Database::connect()->table('users')->where('username', 'nuevo.usuario')->get()->getRowArray();
        $this->assertNotNull($user);
        $this->assertTrue(password_verify('una-frase-larga-segura', $user['password']));
        $acl = new AccessControl();
        $this->assertTrue($acl->allows((int) $user['id'], 'invoices.view'));
        $this->assertTrue($acl->allows((int) $user['id'], 'invoices.process'));
        $this->assertFalse($acl->allows((int) $user['id'], 'security.manage'));
    }

    public function testInvalidAccessAndPasswordDoNotCreatePartialAccount(): void
    {
        $response = $this->post('/users/store', $this->payload(['role_ids' => ['999'], 'password_confirm' => 'different']));
        $response->assertStatus(422);
        $this->assertSame(1, Database::connect()->table('users')->countAllResults());
        $this->assertStringNotContainsString('value="una-frase-larga-segura"', $response->getBody());
    }

    public function testAdministrationCannotBeGrantedAsIndividualPermission(): void
    {
        $this->post('/users/store', $this->payload(['permission_ids' => ['3']]))->assertStatus(422);
        $this->assertSame(1, Database::connect()->table('users')->countAllResults());
    }

    public function testCannotRemoveOwnAdminRole(): void
    {
        $this->post('/users/update/1', $this->payload(['user_name' => 'administrator', 'password' => '', 'password_confirm' => '']))->assertStatus(422);
        $this->assertTrue((new AccessControl())->allows(1, 'security.manage'));
    }

    public function testRoleEditorSavesPermissionsAndProtectsSystemRoles(): void
    {
        $this->withSession();
        $this->post('/roles/update/2', [csrf_token() => csrf_hash(), 'role_name' => 'operador', 'description' => 'Operaciones', 'permission_ids' => ['2']])->assertRedirectTo(site_url('roles'));
        $this->assertSame([2], (new AccessAssignments())->selected('permission_role', 'role_id', 2, 'permission_id'));
        $this->post('/roles/update/1', [csrf_token() => csrf_hash(), 'role_name' => 'renamed', 'description' => ''])->assertStatus(422);
    }

    public function testAssignmentFailureRollsBackEarlierChanges(): void
    {
        $access = new AccessAssignments();
        try {
            $access->transaction(function () use ($access) {
                $access->replace('permission_role', 'role_id', 2, 'permission_id', [2]);
                throw new RuntimeException('Simulated failure');
            });
            $this->fail('The operation should fail');
        } catch (RuntimeException $error) {
            $this->assertSame('Simulated failure', $error->getMessage());
        }
        $this->assertSame([1], $access->selected('permission_role', 'role_id', 2, 'permission_id'));
    }

    public function testFormRendersAndEscapesAccountData(): void
    {
        $this->get('/users/create')->assertOK();
        $this->get('/roles/edit/2')->assertOK();
        Database::connect()->table('users')->where('id', 1)->update(['email' => '"><script>alert(1)</script>']);
        $response = $this->get('/users/edit/1');
        $response->assertOK();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $response->getBody());
    }

    public function testEditingRemovesDirectPermissionAndPreservesPassword(): void
    {
        $db = Database::connect();
        $hash = password_hash('previous-secure-password', PASSWORD_DEFAULT);
        $db->table('users')->insert(['id' => 2, 'username' => 'operador', 'email' => 'user@example.com', 'password' => $hash]);
        $db->table('permission_user')->insert(['user_id' => 2, 'permission_id' => 2]);
        $this->post('/users/update/2', $this->payload(['password' => '', 'password_confirm' => '', 'permission_ids' => []]))->assertRedirectTo(site_url('users'));
        $this->assertSame($hash, $db->table('users')->where('id', 2)->get()->getRow('password'));
        $this->assertTrue((new AccessControl())->allows(2, 'invoices.view'));
        $this->assertFalse((new AccessControl())->allows(2, 'invoices.process'));
    }

    public function testMenuUsesEffectivePermissionsWithoutSeparateMenuAssignments(): void
    {
        $db = Database::connect();
        $db->table('menu_items')->insertBatch([
            ['id' => 1, 'name' => 'Facturas', 'url' => '#', 'parent_id' => 0, 'order' => 1, 'icon' => 'fas fa-file'],
            ['id' => 2, 'name' => 'Pendientes', 'url' => 'facturas', 'parent_id' => 1, 'order' => 1, 'icon' => 'fas fa-file'],
            ['id' => 3, 'name' => 'Usuarios', 'url' => 'users', 'parent_id' => 0, 'order' => 2, 'icon' => 'fas fa-user'],
        ]);
        $db->table('role_user')->insert(['user_id' => 2, 'role_id' => 2]);
        $menu = (new \App\Models\MenuModel())->getMenuByUser(2);
        $this->assertCount(1, $menu);
        $this->assertSame('facturas', $menu[0]['children'][0]['url']);
        $db->table('permission_role')->emptyTable();
        $this->assertSame([], (new \App\Models\MenuModel())->getMenuByUser(2));
    }

    public function testOrdinaryUsersCannotCreateAccounts(): void
    {
        Database::connect()->table('role_user')->emptyTable();
        $this->post('/users/store', $this->payload())->assertStatus(403);
        $this->assertSame(1, Database::connect()->table('users')->countAllResults());
    }

    public function testCannotDeleteOwnAccountOrAssignedRole(): void
    {
        $this->withSession();
        $this->post('/users/delete/1', [csrf_token() => csrf_hash()])->assertRedirectTo(site_url('users'));
        $this->assertSame(1, Database::connect()->table('users')->countAllResults());
        $this->post('/roles/delete/1', [csrf_token() => csrf_hash()])->assertRedirectTo(site_url('roles'));
        $this->assertSame(2, Database::connect()->table('roles')->countAllResults());
    }
}
