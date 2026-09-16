# Categoria "Pet Sitter" para empresas

Adicionada a pedido, fora do cronograma das 9 fases da refatoração —
é uma mudança de dado/conteúdo, não de código.

## O que foi feito

Criada a nova categoria de **empresa** "Pet Sitter" (ícone
`bi-person-heart`), que passa a aparecer no filtro de
`public/empresas/empresas.php` junto com Pet Shop, Clínica Veterinária
etc.

## Por que não existia

O sistema tem dois conceitos separados que usam a palavra "pet sitter":

- **Prestadores** (pessoa física autônoma) — tabela `prestadores_servico`,
  com tipos `passeador`, `pet_sitter`, `taxista_pet`. Aparecem em
  `public/agendamentos/prestadores.php`.
- **Empresas** (negócios cadastrados) — tabela `categorias`, filtradas
  em `public/empresas/empresas.php`. Até agora não tinha uma categoria
  pra empresas desse ramo (só pessoa física).

Essa mudança cobre o segundo caso: agora uma empresa (não uma pessoa
física) que presta serviço de pet sitting pode se cadastrar com essa
categoria e aparecer nesse filtro.

## Como aplicar no seu banco

Se vocês já rodaram o `database/petfinder.sql` anteriormente, apliquem
só a migração nova:

```bash
mysql -u seu_usuario -p petfinder < database/migration_020_categoria_pet_sitter.sql
```

Quem for instalar o projeto do zero a partir de agora já recebe essa
categoria automaticamente, porque também atualizei o `petfinder.sql`.

## Verificação

Testei ao vivo (PHP + banco reais): a categoria aparece corretamente no
dropdown de `empresas.php`, sem nenhum erro no log.
