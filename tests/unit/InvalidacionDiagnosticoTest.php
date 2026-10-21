<?php

use App\Libraries\InvalidacionDiagnostico;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/app/Libraries/InvalidacionDiagnostico.php';

final class InvalidacionDiagnosticoTest extends TestCase
{
    public function testComparesDatesWithoutExposingPersonalData(): void
    {
        $evento = [
            'identificacion' => ['fecEmi' => '2026-10-21'],
            'documento' => ['fecEmi' => '2026-10-20', 'nombre' => 'Dato privado'],
            'emisor' => ['nit' => 'Dato privado'],
        ];
        $result = InvalidacionDiagnostico::comparar($evento, $this->firma($evento));
        self::assertTrue($result['firmaDecodificable']);
        self::assertTrue($result['camposCoinciden']);
        self::assertSame('2026-10-20', $result['firmado']['documento']['fecEmi']);
        self::assertStringNotContainsString('Dato privado', json_encode($result));

        $firmado = $evento;
        $firmado['documento']['fecEmi'] = '2026-10-21';
        self::assertFalse(InvalidacionDiagnostico::comparar($evento, $this->firma($firmado))['camposCoinciden']);
    }

    public function testMalformedSignatureDoesNotBreakDiagnosis(): void
    {
        foreach (['', 'invalido', 'header.%%%.signature', 'header.bnVsbA.signature'] as $firma) {
            $result = InvalidacionDiagnostico::comparar([], $firma);
            self::assertFalse($result['firmaDecodificable']);
            self::assertNull($result['camposCoinciden']);
        }
    }

    private function firma(array $evento): string
    {
        return 'header.' . rtrim(strtr(base64_encode(json_encode($evento)), '+/', '-_'), '=') . '.signature';
    }
}
