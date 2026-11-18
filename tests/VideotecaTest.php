<?php

declare(strict_types=1);

/**
 * Testes: Videoteca Educativa (lê o mesmo assets/data/videos.json do baralho da home)
 */

echo "\n--- Testando Videoteca Educativa ---\n";

TestKit::run('Videoteca aceita youtube (link ou ID) e arquivo no formato do videos.json', function () {
    $cats = ['saude' => 'Saúde'];

    $link = Videoteca::validarVideo(['titulo' => 'Vacinas', 'tipo' => 'youtube', 'youtube' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'duracao' => '3:40', 'categoria' => 'saude'], $cats);
    TestKit::assertNotNull($link);
    TestKit::assertEquals('dQw4w9WgXcQ', $link['ref']);
    TestKit::assertEquals('3:40', $link['duracao']);
    TestKit::assertEquals('saude', $link['categoria']);
    TestKit::assertEquals(8, strlen($link['chave']));

    $curto = Videoteca::validarVideo(['titulo' => 'A', 'tipo' => 'youtube', 'youtube' => 'https://youtu.be/dQw4w9WgXcQ'], $cats);
    TestKit::assertEquals('dQw4w9WgXcQ', $curto['ref']);

    $id = Videoteca::validarVideo(['titulo' => 'B', 'tipo' => 'youtube', 'youtube' => 'dQw4w9WgXcQ'], $cats);
    TestKit::assertNotNull($id);

    $up = Videoteca::validarVideo(['titulo' => 'C', 'tipo' => 'arquivo', 'arquivo' => 'uploads/videos/passeios.mp4', 'capa' => 'uploads/videos/passeios.jpg'], $cats);
    TestKit::assertNotNull($up);
    TestKit::assertEquals('uploads/videos/passeios.jpg', $up['capa']);

    $as = Videoteca::validarVideo(['titulo' => 'D', 'tipo' => 'arquivo', 'arquivo' => 'assets/videos/convite.mp4'], $cats);
    TestKit::assertNotNull($as);
});

TestKit::run('Sem categoria (ou desconhecida) cai em "Geral"', function () {
    $v = Videoteca::validarVideo(['titulo' => 'X', 'tipo' => 'youtube', 'youtube' => 'dQw4w9WgXcQ'], ['saude' => 'Saúde']);
    TestKit::assertEquals('geral', $v['categoria']);
    $v2 = Videoteca::validarVideo(['titulo' => 'X', 'tipo' => 'youtube', 'youtube' => 'dQw4w9WgXcQ', 'categoria' => 'inexistente'], ['saude' => 'Saúde']);
    TestKit::assertEquals('geral', $v2['categoria']);
});

TestKit::run('ativo=false oculta o vídeo', function () {
    TestKit::assertNull(Videoteca::validarVideo(['titulo' => 'X', 'tipo' => 'youtube', 'youtube' => 'dQw4w9WgXcQ', 'ativo' => false], []));
});

TestKit::run('Videoteca rejeita itens inseguros ou incompletos', function () {
    $c = [];
    $base = ['titulo' => 'X'];
    TestKit::assertNull(Videoteca::validarVideo($base + ['tipo' => 'youtube', 'youtube' => 'curto'], $c), 'ID curto');
    TestKit::assertNull(Videoteca::validarVideo($base + ['tipo' => 'youtube', 'youtube' => '"><script>aa'], $c), 'ID com caracteres perigosos');
    TestKit::assertNull(Videoteca::validarVideo($base + ['tipo' => 'youtube', 'youtube' => 'https://evil.com/?x=1'], $c), 'Link sem ID');
    TestKit::assertNull(Videoteca::validarVideo($base + ['tipo' => 'arquivo', 'arquivo' => 'assets/videos/../../.env.mp4'], $c), 'Path traversal');
    TestKit::assertNull(Videoteca::validarVideo($base + ['tipo' => 'arquivo', 'arquivo' => 'config/x.mp4'], $c), 'Fora das pastas de vídeo');
    TestKit::assertNull(Videoteca::validarVideo($base + ['tipo' => 'arquivo', 'arquivo' => 'assets/videos/x.php'], $c), 'Extensão não permitida');
    TestKit::assertNull(Videoteca::validarVideo(['titulo' => '', 'tipo' => 'youtube', 'youtube' => 'dQw4w9WgXcQ'], $c), 'Sem título');
    TestKit::assertNull(Videoteca::validarVideo($base + ['tipo' => 'vimeo', 'youtube' => '123'], $c), 'Tipo desconhecido');

    // capa externa ou fora das pastas é descartada (o vídeo continua válido, sem capa)
    $v = Videoteca::validarVideo($base + ['tipo' => 'youtube', 'youtube' => 'dQw4w9WgXcQ', 'capa' => 'https://img.youtube.com/x.jpg'], $c);
    TestKit::assertNotNull($v);
    TestKit::assertEquals('', $v['capa']);
});

TestKit::run('Videoteca::carregar monta abas, adiciona "Geral" só se usada e ignora lixo', function () {
    $r = Videoteca::carregar(
        ['categorias' => ['saude' => 'Saúde', 'Inválida!' => 'x']],
        [
            ['titulo' => 'Ok', 'tipo' => 'youtube', 'youtube' => 'dQw4w9WgXcQ', 'categoria' => 'saude'],
            'texto solto',
            ['titulo' => 'Ruim', 'tipo' => 'youtube', 'youtube' => 'x'],
            ['titulo' => 'Sem cat', 'tipo' => 'arquivo', 'arquivo' => 'assets/videos/a.mp4'],
        ]
    );
    TestKit::assertEquals(2, count($r['videos']));
    TestKit::assertEquals(['saude' => 'Saúde', 'geral' => 'Geral'], $r['categorias']);

    $sem = Videoteca::carregar(['categorias' => ['saude' => 'Saúde']], [['titulo' => 'Ok', 'tipo' => 'youtube', 'youtube' => 'dQw4w9WgXcQ', 'categoria' => 'saude']]);
    TestKit::assertEquals(['saude' => 'Saúde'], $sem['categorias']);
});

TestKit::run('lerJson: arquivo ausente ou quebrado devolve lista vazia', function () {
    TestKit::assertEquals([], Videoteca::lerJson('/caminho/que/nao/existe.json'));
    $tmp = tempnam(sys_get_temp_dir(), 'vt');
    file_put_contents($tmp, '{ quebrado');
    TestKit::assertEquals([], Videoteca::lerJson($tmp));
    file_put_contents($tmp, '{"videos":[{"titulo":"A"}]}');
    TestKit::assertEquals(1, count(Videoteca::lerJson($tmp)));
    unlink($tmp);
});

TestKit::run('assets/data/videos.json e config/videoteca.php reais carregam sem erro', function () {
    $r = Videoteca::carregar();
    TestKit::assertTrue(count($r['categorias']) > 0);
    TestKit::assertTrue(is_array($r['videos']));
});
