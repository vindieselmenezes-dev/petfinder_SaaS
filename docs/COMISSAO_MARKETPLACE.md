# Comissão simulada do marketplace

Implementado a pedido, fora do cronograma das 9 fases — funcionalidade
nova, não parte da refatoração original.

## ⚠️ Passo obrigatório antes de usar

Rode a migração no seu banco (pelo phpMyAdmin, aba Importar, como da
última vez):

```
database/migration_021_comissao_marketplace.sql
```

Sem isso, o checkout continua funcionando normalmente (o código foi
escrito pra não quebrar se a migração não tiver sido aplicada — a
comissão simplesmente fica zerada), mas ninguém vê a comissão calculada
até a migração rodar.

## O que foi feito

- **Taxa configurável**: nova chave `comissao_marketplace_percentual`
  na tabela `configuracoes` (padrão: `10`, ou seja, 10%). Pra mudar a
  taxa, basta editar essa linha na tabela — não precisa mexer em código.
- **Cálculo automático no checkout** (`app/Models/Pedido.php`): cada
  item do pedido agora grava `valor_comissao` (quanto a plataforma
  reteria) e `valor_repasse` (quanto a empresa receberia), calculados a
  partir da taxa vigente no momento da compra. Pedidos futuros com uma
  taxa diferente não afetam o valor já gravado nos pedidos antigos.
- **Nova tela pra empresa**: `loja/vendas_empresa.php` — acessível pelo
  botão "💰 Ver Vendas" na tela de produtos da empresa
  (`meus_produtos.php`). Mostra cada venda com subtotal, comissão e
  repasse, mais um resumo total no topo.

## Por que uma tela nova

O sistema já tinha `meus_pedidos.php` (o histórico de COMPRAS do
cliente), mas **não existia nenhuma tela pra empresa ver suas próprias
VENDAS** — sem isso, calcular a comissão não teria como ser conferido
por quem realmente precisa dessa informação.

## Continua 100% simulado

Como conversamos, nenhum valor é transferido de verdade — é só cálculo
e registro no banco, igual ao resto do checkout (que já marca o pedido
como "Pago" sem processar pagamento real). Se decidirem integrar um
gateway de pagamento com split real no futuro, essa mesma lógica de
cálculo continua válida — só passa a alimentar a chamada da API em vez
de só gravar no banco.

## Verificação

- `php -l` em todos os arquivos — zero erros
- Suíte de testes existente (82 testes) e a suíte PHPUnit (30 testes)
  continuam passando
- Testado ao vivo: uma compra real via `Pedido::criar()` gravou
  R$ 76,00 de subtotal → R$ 7,60 de comissão (10%) → R$ 68,40 de
  repasse, e a tela `vendas_empresa.php` exibiu esse valor corretamente
  pro usuário dono da empresa (e negou acesso a quem não é dono/equipe,
  reaproveitando a checagem `EmpresaAcesso` que já existia)
