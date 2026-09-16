<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Testes da classe Url (app/Core/Url.php).
 *
 * Url::base() lê a variável de ambiente APP_BASE_PATH, então cada
 * teste limpa essa variável antes e depois, para não vazar para os
 * outros testes (e não depender de como o .env de quem roda os
 * testes está configurado).
 */
final class UrlTest extends TestCase
{
    protected function setUp(): void
    {
        putenv('APP_BASE_PATH');
    }

    protected function tearDown(): void
    {
        putenv('APP_BASE_PATH');
    }

    public function testBaseEhVaziaQuandoNaoConfigurada(): void
    {
        $this->assertSame('', Url::base());
    }

    public function testBaseUsaVariavelDeAmbienteSemBarraNoFinal(): void
    {
        putenv('APP_BASE_PATH=/petfinder-SaaS/');

        $this->assertSame('/petfinder-SaaS', Url::base());
    }

    public function testPaginaSemModuloConhecidoFicaDiretoEmPublic(): void
    {
        $this->assertSame('/public/login.php', Url::pagina('login.php'));
    }

    public function testPaginaComModuloConhecidoIncluiAPasta(): void
    {
        $this->assertSame('/public/pets/cadastrar_pet.php', Url::pagina('cadastrar_pet.php'));
        $this->assertSame('/public/empresas/empresa.php', Url::pagina('empresa.php'));
        $this->assertSame('/public/loja/carrinho.php', Url::pagina('carrinho.php'));
    }

    public function testPaginaRespeitaABaseConfigurada(): void
    {
        putenv('APP_BASE_PATH=/petfinder-SaaS');

        $this->assertSame(
            '/petfinder-SaaS/public/pets/cadastrar_pet.php',
            Url::pagina('cadastrar_pet.php')
        );
    }

    public function testAssetMontaCaminhoDentroDeAssets(): void
    {
        $this->assertSame('/assets/css/dashboard.css', Url::asset('css/dashboard.css'));
        $this->assertSame('/assets/css/dashboard.css', Url::asset('/css/dashboard.css'));
    }

    public function testUploadMontaCaminhoDentroDeUploads(): void
    {
        $this->assertSame('/uploads/pets/foto.jpg', Url::upload('pets/foto.jpg'));
    }

    public function testAjaxMontaCaminhoDentroDeAppAjax(): void
    {
        $this->assertSame('/app/ajax/listar_racas.php', Url::ajax('listar_racas.php'));
    }

    public function testRaizMontaCaminhoNaBaseDoProjeto(): void
    {
        $this->assertSame('/index.html', Url::raiz('index.html'));
        $this->assertSame('/manifest.json', Url::raiz('/manifest.json'));
    }
}
