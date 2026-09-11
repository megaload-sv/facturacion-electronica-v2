<?php

use App\Filters\AuthFilter;
use App\Filters\PermissionFilter;
use App\Filters\PrivateHeaders;
use App\Libraries\AccessControl;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class SecurityHardeningTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $db = Database::connect('tests');
        $this->assertSame('SQLite3', $db->DBDriver);
        foreach ([
            'users' => 'id INTEGER PRIMARY KEY, username TEXT, email TEXT, password TEXT, created_at TEXT, updated_at TEXT',
            'roles' => 'id INTEGER PRIMARY KEY, role_name TEXT',
            'role_user' => 'user_id INTEGER, role_id INTEGER',
            'permissions' => 'id INTEGER PRIMARY KEY, permission_name TEXT',
            'permission_user' => 'user_id INTEGER, permission_id INTEGER',
            'permission_role' => 'role_id INTEGER, permission_id INTEGER',
        ] as $table => $fields) {
            $name = $db->prefixTable($table);
            $db->query("CREATE TABLE IF NOT EXISTS $name ($fields)");
            $db->table($table)->emptyTable();
        }
        $db->table('users')->insert(['id' => 1, 'username' => 'fixture', 'password' => password_hash('test-password', PASSWORD_DEFAULT)]);
        $db->table('roles')->insertBatch([['id' => 1, 'role_name' => 'admin'], ['id' => 2, 'role_name' => 'viewer']]);
        $db->table('permissions')->insertBatch([['id' => 1, 'permission_name' => 'invoices.process'], ['id' => 2, 'permission_name' => 'security.manage']]);
        session()->remove(['logged_in', 'user_id', 'authenticated_at', 'last_activity', 'credential_version']);
    }

    public function testPermissionsDenyByDefaultAndCannotDelegateAdministration(): void
    {
        $acl = new AccessControl();
        $this->assertFalse($acl->allows(1, 'invoices.process'));
        Database::connect()->table('permission_user')->insert(['user_id' => 1, 'permission_id' => 1]);
        $this->assertTrue($acl->allows(1, 'invoices.process'));
        $this->assertFalse($acl->allows(1, 'invoices.invalidate'));
        Database::connect()->table('permission_user')->insert(['user_id' => 1, 'permission_id' => 2]);
        $this->assertFalse($acl->allows(1, 'security.manage'));
    }

    public function testAdministratorAndRolePermissionsAreReadFresh(): void
    {
        $db = Database::connect();
        $acl = new AccessControl();
        $db->table('role_user')->insert(['user_id' => 1, 'role_id' => 1]);
        $this->assertTrue($acl->allows(1, 'security.manage'));
        $db->table('role_user')->emptyTable();
        $db->table('role_user')->insert(['user_id' => 1, 'role_id' => 2]);
        $db->table('permission_role')->insert(['role_id' => 2, 'permission_id' => 1]);
        $this->assertTrue($acl->allows(1, 'invoices.process'));
        $this->assertFalse($acl->allows(1, 'security.manage'));
        $db->table('permission_role')->emptyTable();
        $this->assertFalse($acl->allows(1, 'invoices.process'));
    }

    public function testAnonymousCannotReachAdministration(): void
    {
        $this->get('/users')->assertRedirectTo('/login');
    }

    public function testLoginHasCsrfAndNoDebugToolbar(): void
    {
        $result = $this->get('/login');
        $result->assertOK();
        $this->assertStringContainsString(csrf_token(), $result->getBody());
        $this->assertStringNotContainsString('debugbar_loader', $result->getBody());
        $result->assertHeader('X-Frame-Options', 'DENY');
    }

    public function testLoginRejectsMissingCsrf(): void
    {
        $this->expectException(\CodeIgniter\Security\Exceptions\SecurityException::class);
        $this->post('/login/authenticate', ['username' => 'fixture', 'password' => 'test-password']);
    }

    public function testValidLoginCreatesBoundedSession(): void
    {
        $throttler = $this->createMock(\CodeIgniter\Throttle\ThrottlerInterface::class);
        $throttler->method('check')->willReturn(true);
        \Config\Services::injectMock('throttler', $throttler);
        $result = $this->post('/login/authenticate', [csrf_token() => csrf_hash(), 'username' => 'fixture', 'password' => 'test-password']);
        $result->assertRedirectTo('/dashboard');
        $this->assertTrue(session()->get('logged_in'));
        $this->assertGreaterThan(0, session()->get('authenticated_at'));
        $this->assertNotEmpty(session()->get('credential_version'));
    }

    public function testLoginRateLimitStopsAuthentication(): void
    {
        $throttler = $this->createMock(\CodeIgniter\Throttle\ThrottlerInterface::class);
        $throttler->method('check')->willReturn(false);
        \Config\Services::injectMock('throttler', $throttler);
        $result = $this->post('/login/authenticate', [csrf_token() => csrf_hash(), 'username' => 'fixture', 'password' => 'test-password']);
        $result->assertStatus(429);
        $this->assertFalse((bool) session()->get('logged_in'));
    }

    public function testPermissionFilterBlocksAuthenticatedUserWithoutGrant(): void
    {
        session()->set(['logged_in' => true, 'user_id' => 1]);
        $response = (new PermissionFilter())->before(service('request'), ['invoices.invalidate']);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testSessionExpiresAndDeletedAccountsAreRevoked(): void
    {
        $user = Database::connect()->table('users')->where('id', 1)->get()->getRowArray();
        $valid = ['logged_in' => true, 'user_id' => 1, 'authenticated_at' => time(), 'last_activity' => time(), 'credential_version' => hash('sha256', $user['password'])];
        session()->set($valid);
        $this->assertNull((new AuthFilter())->before(service('request')));
        session()->set('last_activity', time() - 1801);
        $this->assertSame(302, (new AuthFilter())->before(service('request'))->getStatusCode());
        session()->set($valid);
        Database::connect()->table('users')->delete(['id' => 1]);
        $this->assertSame(302, (new AuthFilter())->before(service('request'))->getStatusCode());
    }

    public function testSensitiveRoutesDoNotAcceptGet(): void
    {
        $routes = service('routes');
        $routes->loadRoutes();
        foreach ($routes->getRoutes('GET') as $handler) {
            $this->assertDoesNotMatchRegularExpression('/::(delete|removeRole|procesarDTE|reenviardte|logout|enviarCorreo)/', $handler);
            $this->assertStringNotContainsString('InfoController', $handler);
        }
    }

    public function testFiscalModeRequiresARecognizedExplicitValue(): void
    {
        $config = new \Config\Dte();
        $config->environment = 'production';
        $this->assertSame('production', $config->selectedEnvironment());
        $config->environment = 'development';
        $this->assertSame('development', $config->selectedEnvironment());
        $config->environment = 'invalid';
        $this->expectException(\RuntimeException::class);
        $config->selectedEnvironment();
    }

    public function testCredentialsChangeRevokesExistingSession(): void
    {
        session()->set(['logged_in' => true, 'user_id' => 1, 'authenticated_at' => time(), 'last_activity' => time(), 'credential_version' => hash('sha256', 'previous-hash')]);
        $this->assertSame(302, (new AuthFilter())->before(service('request'))->getStatusCode());
    }
}
