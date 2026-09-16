<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Testes da classe Auth (app/Core/Auth.php).
 *
 * Auth guarda tudo em $_SESSION, então cada teste começa com a sessão
 * zerada (veja setUp()) para um teste nunca vazar estado para o outro.
 *
 * Nota: Auth::login() chama session_regenerate_id(), que em CLI emite
 * um aviso inofensivo assim que qualquer saída de texto já tiver
 * acontecido (ex: os pontinhos de progresso do próprio PHPUnit) — isso
 * não acontece em um servidor web de verdade. O phpunit.xml está
 * configurado para não converter avisos em erro de teste por causa
 * disso (convertWarningsToExceptions="false").
 */
final class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testNaoEstaLogadoPorPadrao(): void
    {
        $this->assertFalse(Auth::check());
        $this->assertNull(Auth::id());
        $this->assertNull(Auth::usuario());
    }

    public function testLoginPreencheASessaoCorretamente(): void
    {
        Auth::login([
            'id' => 42,
            'nome' => 'Maria Tutora',
            'email' => 'maria@example.com',
            'perfil_tipo' => 'cliente',
        ]);

        $this->assertTrue(Auth::check());
        $this->assertSame(42, Auth::id());
        $this->assertSame('Maria Tutora', Auth::nome());
        $this->assertSame('maria@example.com', Auth::email());
        $this->assertSame('cliente', Auth::tipo());
    }

    public function testLoginUsaTipoUsuarioComoFallbackQuandoNaoHaPerfilTipo(): void
    {
        Auth::login([
            'id' => 7,
            'nome' => 'Empresa X',
            'email' => 'contato@empresax.com',
            'tipo_usuario' => 'empresa',
        ]);

        $this->assertSame('empresa', Auth::tipo());
    }

    public function testLoginUsaClienteComoPadraoQuandoNenhumTipoInformado(): void
    {
        Auth::login(['id' => 1, 'nome' => 'Sem Tipo', 'email' => 'x@x.com']);

        $this->assertSame(Auth::TIPO_TUTOR, Auth::tipo());
    }

    public function testNomeEEmailTemPadraoQuandoNaoInformados(): void
    {
        Auth::login(['id' => 1]);

        $this->assertSame('Usuário', Auth::nome());
        $this->assertSame('', Auth::email());
    }

    public function testLogoutLimpaTudo(): void
    {
        Auth::login(['id' => 5, 'nome' => 'Fulano', 'email' => 'f@f.com', 'perfil_tipo' => 'empresa']);
        $this->assertTrue(Auth::check());

        Auth::logout();

        $this->assertFalse(Auth::check());
        $this->assertNull(Auth::id());
        $this->assertSame([], $_SESSION);
    }

    /**
     * @dataProvider provedorDeTipos
     */
    public function testEhAdministradorEEhEmpresaConferemOTipoCorreto(string $tipo, bool $esperaAdmin, bool $esperaEmpresa): void
    {
        Auth::login(['id' => 1, 'perfil_tipo' => $tipo]);

        $this->assertSame($esperaAdmin, Auth::ehAdministrador());
        $this->assertSame($esperaEmpresa, Auth::ehEmpresa());
    }

    public static function provedorDeTipos(): array
    {
        return [
            'administrador' => [Auth::TIPO_ADMINISTRADOR, true, false],
            'empresa' => [Auth::TIPO_EMPRESA, false, true],
            'tutor' => [Auth::TIPO_TUTOR, false, false],
        ];
    }

    public function testTipoEhUmDeAceitaVariosTipos(): void
    {
        Auth::login(['id' => 1, 'perfil_tipo' => Auth::TIPO_EMPRESA]);

        $this->assertTrue(Auth::tipoEhUmDe(Auth::TIPO_EMPRESA, Auth::TIPO_ADMINISTRADOR));
        $this->assertFalse(Auth::tipoEhUmDe(Auth::TIPO_TUTOR, Auth::TIPO_ADMINISTRADOR));
    }

    public function testUsuarioRetornaArrayComTodosOsDados(): void
    {
        Auth::login([
            'id' => 9,
            'nome' => 'Ana',
            'email' => 'ana@example.com',
            'perfil_tipo' => Auth::TIPO_TUTOR,
        ]);

        $this->assertSame([
            'id' => 9,
            'nome' => 'Ana',
            'email' => 'ana@example.com',
            'tipo' => Auth::TIPO_TUTOR,
        ], Auth::usuario());
    }
}
