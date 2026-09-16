<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Testes da classe Flash (app/Core/Flash.php).
 */
final class FlashTest extends TestCase
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

    public function testConsumirRetornaNuloQuandoNaoHaMensagemPendente(): void
    {
        $this->assertNull(Flash::consumir());
    }

    public function testSucessoGravaEConsumirDevolveETipoESeguraCorretos(): void
    {
        Flash::sucesso('Pet cadastrado com sucesso!');

        $flash = Flash::consumir();

        $this->assertSame(['tipo' => 'success', 'mensagem' => 'Pet cadastrado com sucesso!'], $flash);
    }

    public function testErroGravaComTipoDanger(): void
    {
        Flash::erro('Não foi possível salvar.');

        $flash = Flash::consumir();

        $this->assertSame('danger', $flash['tipo']);
        $this->assertSame('Não foi possível salvar.', $flash['mensagem']);
    }

    public function testAvisoGravaComTipoWarning(): void
    {
        Flash::aviso('Sua sessão vai expirar em breve.');

        $flash = Flash::consumir();

        $this->assertSame('warning', $flash['tipo']);
    }

    public function testConsumirLimpaAMensagemDaSessao(): void
    {
        Flash::sucesso('Mensagem única');

        Flash::consumir();
        $segundaChamada = Flash::consumir();

        $this->assertNull($segundaChamada, 'A mensagem deve ser exibida uma única vez.');
    }

    public function testApenasAUltimaMensagemGravadaSobrevive(): void
    {
        Flash::sucesso('Primeira mensagem');
        Flash::erro('Segunda mensagem');

        $flash = Flash::consumir();

        $this->assertSame('Segunda mensagem', $flash['mensagem']);
    }

    public function testConsumirAindaLeChaveLegadaSucesso(): void
    {
        // Compatibilidade com o padrão antigo (Fase 1): páginas que
        // ainda não foram migradas para Flash::sucesso() gravavam
        // diretamente em $_SESSION['sucesso_algumacoisa'].
        $_SESSION['sucesso_pet'] = 'Pet atualizado com sucesso!';

        $flash = Flash::consumir();

        $this->assertSame('success', $flash['tipo']);
        $this->assertSame('Pet atualizado com sucesso!', $flash['mensagem']);
        $this->assertArrayNotHasKey('sucesso_pet', $_SESSION, 'A chave legada deve ser removida após o consumo.');
    }

    public function testConsumirAindaLeChaveLegadaErro(): void
    {
        $_SESSION['erro_produto'] = 'Não foi possível excluir o produto.';

        $flash = Flash::consumir();

        $this->assertSame('danger', $flash['tipo']);
        $this->assertSame('Não foi possível excluir o produto.', $flash['mensagem']);
    }

    public function testRenderImprimeAlertaEscapado(): void
    {
        Flash::sucesso('<script>alert(1)</script>');

        ob_start();
        Flash::render();
        $html = ob_get_clean();

        $this->assertStringContainsString('alert-success', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html, 'A mensagem deve ser escapada, nunca reproduzida crua (proteção contra XSS).');
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testRenderNaoImprimeNadaQuandoNaoHaMensagem(): void
    {
        ob_start();
        Flash::render();
        $html = ob_get_clean();

        $this->assertSame('', $html);
    }
}
