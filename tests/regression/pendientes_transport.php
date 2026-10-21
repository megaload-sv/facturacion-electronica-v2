<?php

/** Prueba aislada del controlador real, sin conexiones al ERP, MH, SMTP o BD.
 * Ejecutar en procesos separados: php tests/regression/pendientes_transport.php production
 * Repetir con development y testing.
 */
namespace App\Controllers {
    class BaseController
    {
        protected $response;
    }
}

namespace {
    define('ENVIRONMENT', $argv[1] ?? 'production');
    require dirname(__DIR__, 2) . '/app/Controllers/FacturasController.php';

    function check(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new \RuntimeException($message);
        }
    }

    function service($name)
    {
        check($name === 'curlrequest', 'Servicio inesperado');
        return $GLOBALS['upstream'];
    }

    function log_message($level, $message): void {}

    $cases = [
        ['https://erp.example.test/api/', true],
        ['http://localhost:81/api/', ENVIRONMENT === 'development'],
        ['http://erp.example.test/api/', ENVIRONMENT === 'development'],
        ['ftp://erp.example.test/api', false],
        ['', false],
        ['//erp.example.test/api', false],
    ];
    foreach ($cases as [$url, $allowed]) {
        $response = new class {
            public int $status = 200;
            public $data;
            public function setStatusCode($status): self { $this->status = $status; return $this; }
            public function setJSON($data): self { $this->data = $data; return $this; }
        };
        $security = new class($url) {
            public function __construct(private string $url) {}
            public function getConfigByEnvironment(): array { return ['urlApiFacturas' => $this->url]; }
        };
        $GLOBALS['upstream'] = new class {
            public array $calls = [];
            public bool $fail = false;
            public function get($url, $options): object
            {
                $this->calls[] = [$url, $options];
                if ($this->fail) { throw new \RuntimeException('ERP no disponible'); }
                return new class {
                    public function getBody(): string { return '{"facturas":[]}'; }
                };
            }
        };
        $reflection = new \ReflectionClass(\App\Controllers\FacturasController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $reflection->getProperty('modelSeguridad')->setValue($controller, $security);
        $reflection->getProperty('response')->setValue($controller, $response);
        $controller->pendientes(0, 200);
        check($response->status === ($allowed ? 200 : 503), ENVIRONMENT . ': estado incorrecto para ' . $url);
        check(count($GLOBALS['upstream']->calls) === ($allowed ? 1 : 0), 'Solicitud no autorizada al ERP');
        if ($allowed) {
            [$target, $options] = $GLOBALS['upstream']->calls[0];
            check($target === rtrim($url, '/') . '/1/100', 'Se alteró la paginación');
            check($options['verify'] === true, 'Se deshabilitó la verificación TLS');
            check($options['allow_redirects'] === false, 'Se habilitaron redirecciones');
            check($options['timeout'] === 20 && $options['connect_timeout'] === 5, 'Se alteraron los tiempos de espera');
            check($response->data === ['facturas' => []], 'Se alteró la respuesta del ERP');
            $GLOBALS['upstream']->fail = true;
            $controller->pendientes();
            check($response->status === 502, 'Un error del ERP debe devolver 502');
        }
    }
    echo ENVIRONMENT . ': 6 escenarios correctos; sin conexiones externas.' . PHP_EOL;
}
