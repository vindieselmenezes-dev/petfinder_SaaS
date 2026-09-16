# Fase 4 — Helper de foto (eliminar duplicação)

## O problema

A lógica de "monta o caminho da foto se o arquivo existir, senão usa uma
imagem padrão" estava copiada e colada em ~25 lugares diferentes, com
pequenas variações de um arquivo pro outro:

```php
// variação 1 (if/else com variável)
if (!empty($pet['foto']) && file_exists("../../uploads/pets/" . $pet['foto'])) {
    $caminhoFoto = "../../uploads/pets/" . $pet['foto'];
} else { ... }

// variação 2 (ternário)
$capa = !empty($empresa["capa"])
    ? "../../uploads/empresas/" . $empresa["capa"]
    : "../../assets/img/pets/sem-foto.png";

// variação 3 (if direto no template)
<?php if (!empty($s['pet_foto']) && file_exists(__DIR__ . '/../../uploads/pets/' . $s['pet_foto'])): ?>
    <img src="../../uploads/pets/<?= htmlspecialchars($s['pet_foto']); ?>">
```

Além de duplicado, esse código tinha caminhos fixos (`../../uploads/...`)
— exatamente o tipo de coisa que quebra silenciosamente numa próxima
reorganização de pastas, como a que fizemos na Fase 2.

## A solução: `app/Helpers/Foto.php`

```php
Foto::existe($pet['foto'], 'pets')   // bool — o arquivo existe de verdade?
Foto::url($pet['foto'], 'pets')      // URL pronta, com fallback automático
```

`Foto::url()` já resolve pra imagem padrão (`assets/img/pets/sem-foto.png`)
quando o campo está vazio ou o arquivo não existe de fato em `/uploads`.
Por baixo dos panos usa os novos `Url::upload()` e `Url::asset()`, então
também não depende de caminho fixo — se o projeto mudar de pasta de novo,
só se mexe no `Url.php`.

## O que foi migrado

Todas as ~29 ocorrências encontradas, nos módulos `pets/`, `empresas/`,
`loja/` e `agendamentos/` — cobrindo pets, empresas, produtos e
prestadores de serviço.

## Bônus: dois bugs reais encontrados e corrigidos no caminho

1. **Tags de SEO desatualizadas.** `empresa.php`, `produto.php`,
   `pet.php` e `identidade_pet.php` geram `<meta property="og:url">` e
   `<link rel="canonical">` a partir de um caminho como
   `"public/empresa.php?id=..."` — que ficou desatualizado depois da
   Fase 2 (a página é `public/empresas/empresa.php` agora). Corrigido
   pra usar `Url::pagina('empresa.php')`.
2. **Imagem de OG sem o prefixo do site.** O `$seoImagem` desses mesmos
   arquivos montava o caminho da imagem "na unha" (`"uploads/empresas/" .
   $capa`), sem considerar que o site pode estar hospedado numa subpasta
   (`APP_BASE_PATH`). Ao trocar por `Foto::url()`, isso passou a ser
   resolvido automaticamente.

## Verificação

Rodei uma varredura em todo `public/` confirmando que não sobrou nenhuma
referência crua a `uploads/pets`, `uploads/empresas`, `uploads/produtos`
ou `uploads/prestadores` fora do próprio `Foto.php`/`Url.php`, e conferi
o balanceamento de chaves/parênteses de todos os arquivos alterados.
