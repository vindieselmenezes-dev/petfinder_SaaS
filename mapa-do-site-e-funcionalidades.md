# PetFinder Brasil — Mapa do Site e Funcionalidades

> Levantado a partir do código em `petfinder-SaaS.zip` (122 páginas em `public/`, 27 models, 8 módulos). Refere-se ao estado atual do projeto, já incluindo as melhorias implementadas nesta conversa (moderação de campanhas, descadastro de newsletter, rate limiting, sitemap/robots).

## Perfis de usuário

| Perfil | O que faz no site |
|---|---|
| **Visitante** (sem login) | Navega pets, empresas, produtos, prestadores e campanhas; usa contato e newsletter |
| **Tutor** | Cadastra pets, adota, favorita, compra na loja, agenda serviços, fala com suporte |
| **Empresa** (petshop, clínica, hotel...) | Painel B2B: catálogo, vendas, agenda, prontuários, planos pagos |
| **Prestador autônomo** (adestrador, pet sitter...) | Perfil de serviço, recebe solicitações, avaliações |
| **Parceiro/ONG** | Publica campanhas de doação, eventos e ações de adoção |
| **Administrador** | Modera parceiros/campanhas, gerencia usuários, suporte, mensagens de contato |

---

## 1. Mapa do site

Convenção: 🌐 público (indexável) · 🔒 exige login · 🛡️ exige perfil admin. Caminhos reais após a reorganização por módulo (`app/Core/Url.php` resolve isso automaticamente em todo link interno).

### Home e institucional (raiz do projeto)
- 🌐 `/index.html` — página inicial
- 🌐 `/public/sobre.php`, `/public/contato.php`, `/public/ajuda.php`, `/public/blog.php`
- 🌐 `/public/termos.php`, `/public/privacidade.php`
- 🌐 `/public/descadastrar_newsletter.php` — descadastro (link + formulário manual)
- 🌐 `/sitemap.php` (e `/sitemap.xml` via rewrite), `/robots.txt`

### `pets/` — Pets, adoção, perdidos & encontrados
- 🌐 `pets_adocao.php`, `pets_perdidos.php`, `pets_encontrados.php`, `pets_adotados.php` — vitrines públicas
- 🌐 `pet.php?id=` — ficha do pet
- 🌐 `buscar_pets.php` — busca com filtros
- 🌐 `identidade_pet.php` — carteirinha/identidade digital do pet (pensada para QR code numa plaquinha física)
- 🔒 `cadastrar_pet.php`, `editar_pet.php`, `excluir_pet.php`, `excluir_imagem_pet.php`
- 🔒 `meus_pets.php`, `pets_tutor.php`, `historico_pet.php`
- 🔒 `favoritar.php`, `meus_favoritos.php`
- 🔒 `solicitar_adocao.php`, `solicitacoes_recebidas.php`, `minhas_solicitacoes.php`
- 🔒 `alerta_perdido.php` / `processa_alerta.php` / `testar_alerta.php` — emissão de alerta de pet perdido
- 🔒 `marcar_pet_recuperado.php`

### `empresas/` — Empresas parceiras (B2B: petshop, clínica, hotel...)
- 🌐 `empresas.php` — diretório público, `empresa.php?id=` — perfil da empresa
- 🌐 `planos.php` — planos de assinatura B2B
- 🔒 `cadastrar_empresa.php`, `cadastro_empresa.php`, `editar_empresa.php`, `excluir_empresa.php`, `excluir_imagem_empresa.php`
- 🔒 `minhas_empresas.php`, `painel_b2b.php` — painel da empresa dona
- 🔒 `avaliar_empresa.php`, `alterar_destaque.php`
- 🔒 `simular_assinatura.php`, `simular_faturamento.php` — simulação de plano/cobrança
- 🔒 `processa_impersonate.php` — admin acessando como uma empresa (suporte)

### `loja/` — Marketplace de produtos
- 🌐 `produtos.php`, `produto.php?id=`, `ofertas.php`, `vitrine.php`
- 🔒 `carrinho.php`, `adicionar_carrinho.php`, `atualizar_carrinho.php`, `remover_carrinho.php`
- 🔒 `checkout.php`, `processa_checkout.php`, `pedido_confirmado.php`, `meus_pedidos.php`, `atualizar_status_pedido.php`
- 🔒 `favoritar_produto.php`, `meus_produtos_favoritos.php`
- 🔒 (empresa) `cadastrar_produto.php`, `editar_produto.php`, `excluir_produto.php`, `excluir_imagem_produto.php`, `meus_produtos.php`, `novo_item_catalogo.php`, `processa_item_catalogo.php`, `vendas_empresa.php`

### `agendamentos/` — Prestadores de serviço & agenda
- 🌐 `prestadores.php`, `prestador.php?id=`
- 🌐 Landing pages de categoria: `adestramento.php`, `banho_e_tosa.php`, `clinica_veterinaria.php`, `hotelzinho.php`
- 🔒 `cadastrar_prestador.php`, `avaliar_prestador.php`
- 🔒 `agendar_consulta.php`, `minhas_consultas.php`, `consultas_empresa.php`
- 🔒 `solicitar_servico_empresa.php` / `solicitar_servico_prestador.php`, `solicitacoes_empresa.php` / `solicitacoes_prestador.php`, `minhas_solicitacoes_empresa.php` / `minhas_solicitacoes_prestador.php`
- 🔒 Prontuário veterinário: `novo_prontuario.php`, `historico_prontuario.php`, `processa_prontuario.php`, `retificar_prontuario.php`, `processa_retificacao.php`

### `parceiros/` — ONGs, campanhas, doações e eventos
- 🌐 `parceiros.php` — diretório, `parceiro.php?id=` — perfil, `campanha.php?id=` — campanha/evento/doação
- 🔒 `cadastrar_parceiro.php`, `painel_parceiro.php`, `campanha_form.php`, `campanha_acoes.php`, `apoios_campanha.php`
- 🛡️ `admin_parceiros.php` — moderação de parcerias
- 🛡️ `admin_campanhas.php` — moderação de campanhas (opcional, por configuração — ver seção 2)

### `suporte/` — Atendimento
- 🔒 `suporte.php`, `chamado.php`, `novo_chamado.php`, `processa_chamado.php`, `conversa.php`, `conversas.php`, `encerrar_suporte.php`
- 🛡️ `suporte_admin.php`

### `conta/` — Conta do usuário e segurança
- 🔒 `meu_perfil.php`, `endereco.php`, `notificacoes.php`, `onboarding.php`
- 🔒 `alterar_senha.php`, `seguranca.php`, `2fa.php` (verificação em duas etapas)
- 🌐 `esqueci_senha.php`, `redefinir_senha.php` (fluxo de recuperação, público por natureza)
- 🌐 `login.php`, `cadastro.php`, `logout.php` (raiz de `public/`, fora dos módulos)

### `admin/` — Administração geral
- 🛡️ `admin_usuarios.php`, `admin_usuario_detalhe.php`
- 🛡️ `admin_contatos.php` — mensagens recebidas pelo formulário de contato
- 🛡️ `dashboard.php` — visão geral com indicadores (parcerias pendentes, campanhas pendentes, resumo de vendas etc.)

---

## 2. Lista de funcionalidades

### Pets & adoção
- Cadastro de pet com foto, espécie/raça, status (Para Adoção / Perdido / Encontrado / Adotado / Com Tutor)
- Vitrines públicas por status, busca com filtros (espécie, raça, cidade)
- Solicitação de adoção com fluxo de aprovação pelo tutor atual
- Favoritos de pets
- Alerta de pet perdido, com marcação de "recuperado"
- Identidade digital do pet (carteirinha pensada para QR code físico)

### Empresas parceiras (B2B)
- Diretório e perfil público de empresas (petshop, clínica, hotelzinho, banho e tosa)
- Painel B2B com métricas (`MetricaEmpresa`), avaliações, destaque na busca
- Planos de assinatura com simulação de cobrança
- Impersonation controlada por admin para suporte

### Marketplace (loja)
- Catálogo de produtos por empresa, com ofertas e vitrine em destaque
- Carrinho, checkout e histórico de pedidos
- Favoritos de produto

### Prestadores de serviço autônomos
- Perfil de prestador (adestrador, pet sitter etc.), avaliações
- Agendamento de consulta/serviço, com solicitações indo e vindo entre tutor e prestador/empresa
- Prontuário veterinário com histórico e retificação

### ONGs, campanhas e doações
- Cadastro de parceiro (ONG/protetor), com aprovação por admin (`admin_parceiros.php`)
- Campanhas de doação, eventos e ações — três tipos (`Campanha::TIPOS`)
- **Moderação de campanha por publicação (opcional)** — implementada nesta conversa: liga/desliga por `configuracoes.moderacao_campanhas_ativa`; quando ativa, toda publicação nova (e toda edição de uma já aprovada) entra como pendente até um admin aprovar em `admin_campanhas.php`

### Comunicação
- Formulário de contato público, com aviso automático a admins e registro em `admin_contatos.php`
- Newsletter: inscrição pelo rodapé + **descadastro** (link assinado por token HMAC ou formulário manual) — implementado nesta conversa
- Sistema de suporte com tickets e conversas (`chamado`/`conversa`)
- Notificações internas (sino) para eventos como aprovação de parceria/campanha

### Conta e segurança
- Cadastro/login, recuperação de senha, verificação em duas etapas (2FA)
- Limitador de tentativas de login (`LimiteLogin`)
- **Rate limiting genérico por IP** — implementado nesta conversa (`RateLimiter`), aplicado a contato, inscrição e descadastro de newsletter (5–8 envios / 10 min, bloqueio de 30 min)

### Administração
- Gestão de usuários, detalhe de usuário
- Moderação de parceiros e (opcionalmente) de campanhas
- Mensagens de contato centralizadas
- Dashboard com indicadores agregados

### SEO e indexação
- **`sitemap.xml` dinâmico e `robots.txt`** — implementados nesta conversa: sitemap combina páginas estáticas com listagens públicas do banco (pets, empresas, produtos, prestadores, parceiros, campanhas), com cache de 6h; robots.txt bloqueia tudo por padrão e libera só o conteúdo indexável

---

*Este documento reflete o código como está no zip enviado + as mudanças feitas nesta conversa. Não foi feita varredura linha a linha de cada uma das 122 páginas — páginas com nomes ambíguos (`pets_tutor.php`, `em_breve.php`) foram categorizadas pela função mais provável a partir do nome/módulo.*
