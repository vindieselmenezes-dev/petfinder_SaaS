# Fase 7 — Limpeza de arquivos legados

## Removido

**`public/teste_login.php`** — script de debug esquecido no código (ficava
testando qual variável de conexão com banco existia: `$pdo`, `$conn`,
`$db` ou `$conexao`). Não era referenciado por nenhuma outra página nem
link do site — confirmado por busca em todo o projeto antes de remover.

## Esclarecido (não removido)

Dois pares de arquivo com nomes fáceis de confundir ganharam um
comentário no topo explicando a diferença, em vez de serem renomeados
— renomear neste ponto exigiria atualizar o mapa de módulos
(`app/Core/Url.php`), o redirecionamento de compatibilidade e qualquer
link existente, um risco desnecessário para um ganho que um bom
comentário já resolve:

- **`empresas/cadastro_empresa.php`** — cadastro público completo (cria
  usuário + empresa juntos), não exige login.
- **`empresas/cadastrar_empresa.php`** — usado por quem já está logado
  para adicionar mais uma empresa à própria conta.

## Mantido de propósito (não é lixo, é rede de segurança)

**`loja/novo_item_catalogo.php`** e **`loja/processa_item_catalogo.php`**
já eram, desde antes desta refatoração, redirecionamentos legados sem
nenhuma gravação em banco — o comentário original no código já dizia
"mantido só para não quebrar links salvos antigos". Isso é exatamente a
mesma função de proteção que os 100 redirecionamentos de compatibilidade
da Fase 2 cumprem — remover esses dois iria contra esse mesmo princípio.
Se um dia tiverem certeza de que não existe mais nenhum link antigo
apontando pra eles (ex: nenhum e-mail antigo, nenhum favorito de
usuário), aí sim podem ser removidos com segurança.

## Verificação

Rodei a checagem de sintaxe (`php -l`) nos 295 arquivos PHP restantes do
projeto — nenhum erro.
