# Histórico

## Versão 0.1

Implementado

- Cadastro de usuário

- Login

- Dashboard

- Cadastro de Pets

- Upload de Fotos

- Espécies

- Raças

---

## Versão 0.2

Em desenvolvimento

Reconstrução completa do módulo Pets.
---

## Fusão com o projeto "projetointegrador" (integração de novas ideias de serviço)

Migration: `database/migration_014_prestadores_servico.sql`

Implementado

- Novo módulo de Prestadores de Serviço autônomos: Passeador e Pet Sitter
  - Tabelas: `prestadores_servico`, `prestador_servicos`, `prestador_animais_atendidos`, `prestador_disponibilidade`, `prestador_avaliacoes`, `prestador_solicitacoes`
  - Model/Controller: `app/Models/Prestador.php`, `app/Controllers/PrestadorController.php`
  - Cadastro completo do profissional (dados pessoais, experiência, serviços oferecidos, disponibilidade, valores): `public/cadastrar_prestador.php`
  - Diretório público com busca por tipo/cidade/nome: `public/prestadores.php`
  - Perfil público com avaliações e solicitação de serviço: `public/prestador.php`
  - Fluxo de solicitação de passeio/diária: `public/solicitar_servico_prestador.php`, `public/solicitacoes_prestador.php` (recebidas), `public/minhas_solicitacoes_prestador.php` (enviadas)
  - Avaliações: `public/avaliar_prestador.php`
- Página "Identidade Pet" (carteirinha digital com QR code), aproveitando dados já existentes de pets (microchip, status)
  - Nova coluna `pets.token_identidade`
  - `public/identidade_pet.php`
  - Link "🪪 Identidade" em `public/meus_pets.php`
- Páginas temáticas de serviço, reaproveitando o cadastro de empresas já existente e o design/imagens do projetointegrador:
  - `public/hotelzinho.php` (categorias "Hotel para Pets" e "Creche Pet")
  - `public/adestramento.php` (categoria "Adestramento")
  - `public/clinica_veterinaria.php` (categorias "Clínica Veterinária" e "Hospital Veterinário")
- Menu lateral (`app/Includes/menu.php`) e página inicial (`index.html`) atualizados com os novos links

---

## Rodada 2 da fusão: revisão completa do projetointegrador

Migration: `database/migration_015_taxi_pet.sql`

Implementado

- **Táxi Pet**: novo tipo de prestador (junto com Passeador/Pet Sitter), com tabela própria `prestador_veiculo` (tipo de veículo, capacidade, ar-condicionado, caixa de transporte, valor por km/corrida mínima). Ideia trazida da home alternativa `index-petfinder.html` do projetointegrador.
- **Agendamento de Consulta Veterinária** (maior gap encontrado na revisão): o SaaS já tinha as tabelas `consultas`/`veterinarios`/`agenda_veterinaria` prontas, mas só eram usadas do lado da clínica (fechamento de prontuário). Agora o tutor pode solicitar um agendamento de verdade.
  - Model/Controller: `app/Models/Consulta.php`, `app/Controllers/ConsultaController.php`
  - `public/agendar_consulta.php` (tutor solicita), `public/minhas_consultas.php` (tutor acompanha/cancela), `public/consultas_empresa.php` (clínica confirma/atende/conclui)
  - Link "Consultas Agendadas" adicionado em `painel_b2b.php`
- **Banho e Tosa**: nova página temática `public/banho_e_tosa.php` com calculadora de preço por porte (ideia de `servicos.html`), reaproveitando a categoria "Banho e Tosa" que já existia no banco.
- **Clínica Veterinária**: página enriquecida com mais especialidades (Oftalmologia, Dermatologia, Nutrição), seção de Exames/Vacinação/Acompanhamento, e botão "Agendar Consulta" em cada empresa listada.
- Menu e página inicial atualizados com todos os itens acima.
- Testes automatizados novos: `tests/ConsultaTest.php` (8 casos) e testes de Táxi Pet em `tests/PrestadorTest.php` (4 casos) — suíte completa em 65/65, sem warnings.

---

## Rodada 3: Sistema de Planos para Empresas (base pra cobrança automatizada)

Migration: `database/migration_016_planos.sql`

Implementado

- Nova tabela `planos` (Grátis, Profissional, Destaque) com preço, limite de produtos, prioridade de listagem e dias de trial
- `empresas.plano_id` / `plano_iniciado_em` / `plano_expira_em` — toda empresa nova entra automaticamente no plano Grátis
- Model/Controller: `app/Models/Plano.php`, `app/Controllers/PlanoController.php`
- `Empresa::listarAtivas()` agora ordena priorizando empresas com plano em destaque (afeta `empresas.php` e todas as páginas temáticas: hotelzinho, adestramento, banho e tosa, clínica)
- `public/planos.php` — página pública de preços
- `public/simular_assinatura.php` — troca de plano simulada (sem gateway real ainda), seguindo o mesmo padrão de auditoria/transação do `simular_faturamento.php` já existente
- `painel_b2b.php` atualizado com selo do plano atual e link pra trocar
- Cobrança automatizada real (Pix/cartão/boleto via gateway como Mercado Pago/Asaas/Iugu) fica pro próximo passo, quando o gateway for escolhido — a estrutura já está pronta pra receber o webhook.
- Testes novos: `tests/PlanoTest.php` (5 casos). Suíte completa: **70/70**.

---

## Rodada 4: Prioridade Alta da revisão pós-fusão

Sem migration nova (só código) — reaproveitando estrutura já existente.

Implementado

- **Notificações ligadas de verdade**: os módulos de Passeador/Pet Sitter/Táxi Pet e Agendamento de Consulta agora disparam notificações usando o `NotificacaoController` já existente no sistema (mesmo usado em adoção, chat e pedidos):
  - Nova solicitação de serviço → notifica o prestador
  - Solicitação aceita/recusada/concluída/cancelada → notifica o tutor
  - Nova consulta agendada → notifica o veterinário
  - Consulta confirmada/em atendimento/concluída/cancelada → notifica o tutor
  - Tutor cancela consulta → notifica o veterinário
- **Limite de produtos por plano, aplicado de verdade**: `Produto::limiteAtingido()` bloqueia o cadastro quando a empresa atinge o limite do plano (5 no Grátis, ilimitado no Profissional/Destaque). A página `cadastrar_produto.php` mostra quantos produtos já foram usados e, ao bater o teto, esconde o formulário e linka pra `planos.php` pra fazer upgrade.
- Corrigido de brinde: bug pré-existente em `ProdutoController::cadastrar()`/`atualizar()` que gerava "Undefined array key" quando peso/altura/largura/comprimento/preço de custo não eram enviados.
- Testes novos: notificações verificadas em `PrestadorTest.php` e `ConsultaTest.php`; 3 novos casos de limite de produto em `PlanoTest.php`. Suíte completa: **74/74**, zero warnings.

---

## Rodada 5: Prioridade Média da revisão pós-fusão

Sem migration nova (só código).

Implementado

- **Selo visual de Destaque pro público**: empresas no plano Destaque agora aparecem com uma faixa "⭐ Destaque" e borda dourada em todos os lugares onde já eram priorizadas na ordenação — `empresas.php`, `hotelzinho.php`, `adestramento.php`, `banho_e_tosa.php`, `clinica_veterinaria.php` e no perfil (`empresa.php`).
- **Botão "Agendar Consulta" no perfil da empresa**: aparece automaticamente em `empresa.php` quando a categoria é Clínica Veterinária ou Hospital Veterinário, não só nos cards da listagem temática.
- **Consulta concluída agora vira prontuário de verdade**: `consultas_empresa.php` ganhou um link "📋 Prontuário" pra consultas em atendimento/concluídas. `novo_prontuario.php` e `processa_prontuario.php` foram adaptados pra aceitar um `consulta_id` existente — quando vem de uma consulta que o tutor agendou, o pet e o motivo já vêm pré-preenchidos, e o sistema reaproveita essa mesma consulta em vez de criar uma duplicada (evita erro pré-existente de duplicação de registro).
- Testes novos: verificação de que não é criada consulta duplicada ao registrar o prontuário. Suíte completa: **75/75**, zero warnings.

Com isso, as 6 lacunas identificadas na revisão pós-fusão (2 altas + 3 médias + 1 baixa) estão reduzidas a apenas 1 item de baixa prioridade: escolha de plano já no momento do cadastro da empresa (hoje só dá pra trocar depois, pelo painel).

---

## Rodada 6: última pendência (prioridade baixa) — Plano no cadastro

Sem migration nova (só código).

Implementado

- `cadastrar_empresa.php` (empresa cadastrada por usuário já logado) e `cadastro_empresa.php` (fluxo de conta+empresa juntos, usado pelo botão "Anunciar" da home) agora mostram os 3 planos como cards de seleção, com o Grátis pré-selecionado.
- Se a empresa escolhe um plano pago (Profissional/Destaque) no cadastro, o sistema aplica automaticamente o período de trial, reaproveitando o fluxo já existente de `simular_assinatura.php` (mesma auditoria/transação).
- Teste novo: cadastro de empresa com `plano_id` explícito é respeitado (não cai no Grátis). Suíte completa: **76/76**, zero warnings.

Com isso, todas as 6 lacunas identificadas na revisão pós-fusão (2 altas + 3 médias + 1 baixa) foram resolvidas.

---

## Rodada 7: Período de teste com prazo real (não mais indefinido)

Migration: `database/migration_017_trial_com_prazo.sql`

Decisão de produto: nenhuma empresa ou prestador (Passeador/Pet Sitter/Táxi Pet) fica de graça pra sempre. Todo mundo tem 30 dias de teste; depois disso, precisa assinar um plano pago pra continuar visível e operando.

Implementado

- **Grátis passou a ter prazo** (30 dias) — antes era permanente (`dias_trial = 0`)
- **Prestadores ganharam o mesmo esquema de plano das empresas**: `prestadores_servico.plano_id` / `plano_iniciado_em` / `plano_expira_em`, defaults pro Grátis no cadastro
- **Quando o prazo vence, as duas coisas acontecem, como decidido**:
  - Some das buscas públicas (`Empresa::listarAtivas()` e `Prestador::listarAtivos()` agora excluem quem está com o trial vencido)
  - Painel/gestão trava: `painel_b2b.php` mostra aviso e bloqueia cadastro de novo produto (`cadastrar_produto.php`); prestador com trial vencido não recebe novas solicitações (bloqueado nas duas camadas: UI escondida em `prestador.php` e trava real em `PrestadorController::criarSolicitacao()`)
- **Grátis só vale uma vez**: depois do cadastro, não é possível "renovar" o teste escolhendo Grátis de novo — `planos.php` desabilita a opção pra quem já tem empresa/perfil, e `simular_assinatura.php` bloqueia a tentativa também do lado do servidor
- `planos.php` e `simular_assinatura.php` generalizados pra funcionar tanto com `empresa_id` quanto `prestador_id`
- Testes novos: 6 casos cobrindo trial vencido/renovação pra empresas e prestadores (incluindo forçar a data pro passado via SQL direto, já que não dá pra "viajar no tempo"). Suíte completa: **82/82**, zero warnings.

Com isso, o modelo de negócio de assinatura está completo do ponto de vista de regras — falta só a integração real com um gateway de pagamento pra automatizar a cobrança de verdade (Pix/cartão/boleto).
