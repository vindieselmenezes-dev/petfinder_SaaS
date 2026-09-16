# Testes Automatizados — PetFinder Brasil

## O que é isso

Testes simples, sem dependências externas (sem Composer, sem PHPUnit), que verificam
se as funções principais dos Models (`Usuario`, `Pet`, `Favorito`, `Endereco`) continuam
funcionando do jeito esperado.

Cada teste roda contra o **banco de dados real**, mas cria seus próprios dados de teste
(usuário, pets, endereço) marcados com o prefixo `TESTE_AUTOMATIZADO_PETFINDER`, e **apaga
tudo no final** — não deixa lixo no banco, e não mexe nos seus dados reais.

## Como rodar

⚠️ **Importante: roda pelo terminal (CMD/PowerShell), NUNCA pelo navegador.**
Por isso a pasta `tests` fica fora da pasta `public` — scripts de teste não devem ser
acessíveis publicamente.

1. Abre o terminal do VS Code (menu **Terminal → Novo Terminal**)

2. Roda esse comando (ajuste o caminho se o seu XAMPP não estiver em `C:\xampp`):

```
C:\xampp\php\php.exe C:\xampp\htdocs\PetFinderBrasil\tests\run_all.php
```

Se você já estiver com o terminal aberto dentro da pasta do projeto, pode simplificar:

```
C:\xampp\php\php.exe tests\run_all.php
```

## Como ler o resultado

```
[OK]      cadastrar() cria usuário e emailExiste() passa a retornar true
[FALHOU]  senha nunca é armazenada em texto puro (sempre com hash)
          Motivo: a senha NUNCA deveria estar em texto puro no banco
```

- `[OK]` = passou
- `[FALHOU]` = alguma coisa quebrou, com o motivo explicado logo abaixo

No final aparece um resumo com o total de testes, quantos passaram e quantos falharam.

## Quando rodar

Sempre que você (ou alguém te ajudando) mexer em `Pet.php`, `Usuario.php`, `Favorito.php`
ou `Endereco.php`, vale rodar os testes de novo pra garantir que nada quebrou sem querer.

## Arquivos

- `bootstrap.php` — carrega tudo que os testes precisam (banco, Models, TestKit)
- `TestKit.php` — mini framework com as funções de verificação (assertTrue, assertEquals, etc.)
- `UsuarioTest.php`, `PetTest.php`, `FavoritoTest.php`, `EnderecoTest.php` — os testes de cada Model
- `run_all.php` — o arquivo que você realmente executa; monta o cenário, roda tudo, limpa o banco no final
