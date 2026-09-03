# Funcionalidades do PetFinder Brasil

Levantamento feito a partir do código em `app/Controllers`, `app/Models`, `app/ajax` e das 83 páginas em `public/`.

## Conta e autenticação
- Cadastro de usuário (tutor) e login/logout.
- Recuperação de senha por e-mail ("Esqueci minha senha" → link de redefinição).
- Alteração de senha logado, com validação de força (`ValidacaoSenha`).
- Limite de tentativas de login (`LimiteLoginTest` / model `LimiteLogin`) — proteção contra força bruta.
- Edição de perfil ("Meu perfil") e cadastro de endereço do usuário.
- Proteção CSRF em formulários sensíveis (`Csrf.php`).

## Pets — cadastro e gestão
- Cadastro, edição e exclusão de pets, com upload de múltiplas fotos (galeria) e validação real de imagem (não só extensão).
- Exclusão individual de imagens do pet.
- Listagem "Meus pets" e histórico de eventos/status de cada pet (`historico_pet.php`, tabela de histórico de status).
- Busca de pets por espécie, raça e cidade (listas dinâmicas via AJAX: `listar_racas.php`, `listar_filtros.php`).

## Pets perdidos e encontrados
- Registro de alerta de pet perdido, com captura de geolocalização do navegador.
- Listagem separada de "pets perdidos" e "pets encontrados".
- Marcar pet como recuperado.
- Endpoint de teste de alerta (`testar_alerta.php`) para depuração do fluxo.

## Adoção
- Vitrine pública de pets para adoção e lista de pets já adotados.
- Solicitação de adoção por outro usuário, com mensagem para o tutor.
- Painel de solicitações enviadas ("Minhas solicitações") e recebidas ("Solicitações recebidas").
- Aprovar, rejeitar ou cancelar uma solicitação de adoção.
- Favoritar pets ("Meus favoritos").

## Empresas / parceiros (B2B)
- Cadastro de empresa (petshop, clínica, ONG etc.), com CNPJ, categoria, horários de funcionamento e galeria de imagens.
- Edição e exclusão de empresa, exclusão individual de imagem da galeria.
- Listagem pública de empresas por categoria/cidade/busca e página de destaques.
- Avaliação de empresas por usuários (nota).
- Painel B2B por empresa, com controle de acesso por **equipe** (`empresa_equipe`) e papéis (proprietário, administrador etc.).
- Simulação/alteração do status de faturamento da empresa (Ativo, Atrasado, Suspenso) — restrito a proprietário/administrador ou a um admin da plataforma personificando a empresa.
- **Impersonation**: administrador da plataforma pode "entrar como" uma empresa (com justificativa obrigatória e validação de CSRF) para dar suporte.

## Catálogo, produtos e loja
- Cadastro, edição e exclusão de produtos/serviços por empresa, com imagens, subcategorias e marcas.
- Controle de estoque (quantidade, mínimo, máximo).
- Vitrine de produtos, página de ofertas e página individual de produto.
- Favoritar produtos ("Meus produtos favoritos").
- Carrinho de compras (adicionar/remover itens) e checkout.
- Confirmação de pedido e listagem "Meus pedidos".

## Prontuário veterinário
- Registro de novo prontuário/atendimento para um pet.
- Histórico de prontuário com retificação (correção com rastro de auditoria — o registro original não é apagado, gera um novo apontando a retificação).

## Mensagens e notificações
- Conversas entre usuários (ex.: tutor ↔ interessado em adoção), com marcação de mensagens como lidas.
- Central de notificações (sistema, adoção, suporte etc.), com contagem de não lidas e "marcar todas como lidas".

## Suporte
- Abertura de chamado de suporte com assunto, descrição e prioridade.
- Histórico de respostas em cada chamado e atualização de status.
- Painel administrativo de suporte (`suporte_admin.php`) para times internos verem/responderem todos os chamados.
- Encerramento de chamado.

## Blog
- Listagem de posts do blog, com comentários e compartilhamento (`BlogComment`, `BlogShare`).

## Administração da plataforma
- Gestão de usuários (listagem e detalhe individual) para administradores.
- Dashboard com estatísticas gerais (`DashboardController::estatisticas`).

## Infraestrutura e experiência
- PWA: manifest, service worker (`sw.js`) e página offline.
- Geolocalização para sugerir empresas/veterinários próximos (`listar_veterinarios_proximos.php`, `geolocalizacao.js`).
- Sugestões de busca dinâmicas (autocomplete) para pesquisa geral e de produtos.
- Sistema de som de confirmação em cliques (`click-sounds.js`, adicionado recentemente) em todos os botões e links de ação do site.

## Testes automatizados
Cobertura em `tests/`: conversas, endereço, favoritos, histórico de status, limite de login, pets, redefinição de senha, solicitações de adoção, suporte e usuários.
