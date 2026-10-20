# Operação dos recursos

Este documento descreve como configurar e operar os recursos que dependem do ambiente: variáveis do `.env`, e-mail, confirmação de e-mail, recuperação de senha, 2FA e notificações push. A seção "Hospedagem no InfinityFree" reúne o que foi aprendido ao publicar o projeto lá.

## Migrations

O arquivo `database/petfinder.sql` é a base. As tabelas e colunas criadas depois estão nas migrations, que devem ser aplicadas **em ordem numérica**. Em especial:

- `migration_013_produto_operacional.sql`: histórico de rastreio, eventos de métricas, assinaturas push (`push_subscriptions`) e campos de onboarding/2FA.
- `migration_012` a `migration_027`: várias tabelas que **não** estão no `petfinder.sql` (prestadores, parceiros, campanhas, mensagens de contato, pedidos de serviço, entre outras). Quem importar só o dump terá erros nessas telas.
- `migration_029_confirmacao_email.sql`: coluna `usuarios.email_verificado_em` e tabela `confirmacao_email_tokens`. **Marca todas as contas existentes como confirmadas**, para ninguém ser barrado no próximo login. Rode uma vez. Se importar um seed depois dela, rode de novo apenas o `UPDATE usuarios SET email_verificado_em = NOW() WHERE email_verificado_em IS NULL;`.

Em hospedagens onde o banco tem outro nome (InfinityFree: `if0_XXXX_petfinder`), apague a linha `USE petfinder;` das migrations que a possuem (por exemplo 012 e 028) e **selecione o banco no phpMyAdmin antes de importar**. Alguns dumps também trazem `CREATE DATABASE`, que a hospedagem não permite.

## Variáveis de ambiente (.env)

O arquivo `.env` fica na raiz do projeto e **nunca** vai para o Git (está no `.gitignore`). O `.env.example` mostra o formato, sem valores secretos.

- **Leitura:** `app/Core/Env.php` carrega o arquivo no início do `bootstrap.php`. O código deve ler configuração por `Env::get('NOME')`, que devolve a string (pode ser vazia) ou `false` se não existir. O ambiente real do servidor vence; depois vale o `.env`.
- **Não use `getenv()` direto** para configuração do `.env`. Hospedagens como o InfinityFree desativam `putenv()`, e nesse caso o `getenv()` nunca enxerga o que está no arquivo. `Env::get()` guarda os valores por conta própria e funciona nos dois casos. (`config/database.php` ainda usa `getenv()`: confira como a conexão é configurada em cada ambiente antes de alterá-lo.)
- **A primeira ocorrência de cada nome vale.** Uma linha repetida mais abaixo é ignorada, e uma linha **vazia** antes da preenchida vence. Mantenha uma única linha por variável e edite a existente em vez de acrescentar outra.
- **Sem aspas e sem espaços** nos valores.
- **Segredos** (`SMTP_PASSWORD`, `PUSH_VAPID_PRIVATE_KEY`, `DB_PASSWORD`, `APP_KEY`) ficam só no `.env` de cada ambiente. Nunca em commits, prints ou conversas. Cada ambiente (local e produção) usa o seu próprio `.env` e o seu próprio par de chaves.
- `APP_ENV=production` força HTTPS e o cookie de sessão `secure`. Use `local` apenas no computador de desenvolvimento. `APP_DEBUG=false` em produção.

## E-mail

O envio passa por `app/Helpers/Mailer.php`, com PHPMailer e SMTP. Duas chaves independentes controlam o comportamento:

| Variável | Efeito |
| --- | --- |
| `EMAIL_ENVIO=1` | Liga o envio real: recuperação de senha, código 2FA, formulário de contato e confirmação de e-mail. |
| `EMAIL_NOTIFICACOES=1` | Além disso, faz **toda** notificação do sistema virar e-mail, sem escolha. Gasta muito do limite diário do provedor; o push já avisa o celular. Mantenha `0`: os eventos importantes já enviam e-mail por conta própria (ver "E-mail por evento"). |

Com as duas em `0`, o sistema só grava em `logs/emails.log`. Exemplo com Gmail (senha de app, não a senha da conta):

```text
EMAIL_ENVIO=1
EMAIL_NOTIFICACOES=0
EMAIL_REMETENTE=seuemail@gmail.com
EMAIL_CONTATO=seuemail@gmail.com
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_AUTH=1
SMTP_USERNAME=seuemail@gmail.com
SMTP_PASSWORD=senha-de-app-sem-espacos
```

- `EMAIL_REMETENTE` e `SMTP_USERNAME` devem ser o **mesmo endereço**. Em provedores de envio (Brevo e similares) o remetente precisa estar validado.
- A senha de app do Google tem 16 caracteres, sem espaços.
- Há um tempo limite de 15 segundos na conexão SMTP, para uma conexão bloqueada não travar a página.
- **Logs:** fora do ambiente local, `logs/emails.log` grava só destinatário e assunto, **sem o corpo** (que contém links de redefinição e de confirmação). A pasta `logs/` é bloqueada pelo `.htaccess` e por um `.htaccess` próprio criado pelo `Mailer`.
- **Falhas de envio** ficam em `storage/logs/php_errors.log`, na linha `Falha SMTP EcoSistemPet: ...`. É o primeiro lugar a olhar quando um e-mail não chega.

`Mailer::disponivel()` diz se o site consegue enviar e-mail: verdadeiro com `EMAIL_ENVIO=1`, `EMAIL_NOTIFICACOES=1` ou `APP_ENV=local`. As telas que dependem de e-mail usam essa função para **não prometer o que não cumprem**:

- sem e-mail disponível, o login troca "Esqueci minha senha" por "Fale com o suporte";
- a tela de recuperação mostra "temporariamente indisponível";
- a opção de ativar o 2FA some (quem já tem 2FA ainda pode desativar);
- o cadastro vai direto ao login, sem prometer link;
- a confirmação de e-mail deixa de ser exigida.

### E-mail por evento

Toda notificação aparece no sino e dispara push. Ela só vira **e-mail** quando quem a cria pede: `Notificacao::criar(..., enviarEmail: true)`. Com `EMAIL_ENVIO=1` e `EMAIL_NOTIFICACOES=0`, enviam e-mail apenas estes eventos:

- **Pedidos da loja:** atualização de status do pedido (`Pedido.php`).
- **Consultas veterinárias:** nova consulta (veterinário), mudança de status (tutor) e cancelamento pelo tutor (veterinário).
- **Serviços de empresas e prestadores:** novo pedido e cancelamento (empresa), mudança de status (tutor), nova solicitação (prestador) e orçamento recebido (tutor).
- **Adoção:** novo pedido (dono do pet), aprovação e recusa (solicitante).
- **Moderação:** resultado da moderação de campanha e de parceria.
- **Suporte e contato:** novo chamado (administradores), resposta do suporte ao usuário e nova mensagem do formulário de contato (administradores).

Ficam **só no sino e no push**: nova mensagem de conversa, pet favoritado, alertas de pet perdido, novos apoios em campanha, avisos de pedido compartilhado a empresas e respostas em chamado dirigidas a administradores. Para incluir ou tirar um evento, acrescente ou remova `enviarEmail: true` na chamada de `criar(...)` correspondente.

O link do e-mail e do push é resolvido por `Notificacao::resolverLink()`: aceita nome de página (`minhas_consultas.php?id=1`), caminho (`/public/...`) ou URL completa, e devolve um endereço que abre. No e-mail vai com o domínio (da requisição ou da `APP_URL`).

## Confirmação de e-mail

Ao se cadastrar, a pessoa recebe um link (válido por 24 horas) em `public/conta/confirmar_email.php`. O link só **mostra** um botão; quem confirma é o clique (POST), para antivírus e leitores de e-mail não gastarem o token. A tela `reenviar_confirmacao.php` pede um novo link, com limite de 5 pedidos por IP a cada 15 minutos e 3 por conta por hora.

- `EXIGIR_CONFIRMACAO_EMAIL=1` (padrão): o login é barrado até confirmar. Com `0`, o link é enviado mas não bloqueia.
- A exigência só vale com e-mail disponível (`Mailer::disponivel()`); sem isso o cadastro não trancaria ninguém.
- Quem redefine a senha pelo link do e-mail passa a contar como confirmado.
- Se o e-mail falhar e as pessoas ficarem sem o link, as saídas rápidas são `EXIGIR_CONFIRMACAO_EMAIL=0` ou, para uma conta específica, `UPDATE usuarios SET email_verificado_em = NOW() WHERE email = '...';`.

## Recuperação de senha

`public/conta/esqueci_senha.php` envia um link válido por 1 hora para `redefinir_senha.php`. A resposta é sempre a mesma, exista a conta ou não. Limites: 5 pedidos por IP a cada 15 minutos e 3 e-mails por hora por conta. Só funciona com e-mail disponível; em `APP_ENV=local` o link aparece na tela para teste. O administrador pode redefinir a senha de um usuário em Gestão de Usuários, na seção "Redefinir senha", como alternativa.

## 2FA

A tela `public/conta/seguranca.php` (menu **Conta, Segurança (2FA)**) ativa a verificação em duas etapas por código de 6 dígitos enviado ao e-mail da conta.

- Ativar e desativar exigem a **senha atual** (limite de 5 tentativas a cada 15 minutos).
- No login, depois da senha, a sessão só é aberta após o código. O código vale 10 minutos; são 5 tentativas a cada 10 minutos, com bloqueio de 15 minutos ao estourar.
- A tela do código tem **Reenviar código** (3 novos códigos a cada 10 minutos; cada reenvio invalida o anterior).
- O banco guarda só um HMAC de 10 caracteres do código, feito com a `APP_KEY` (confira que ela está preenchida). Quem lesse o banco não veria os códigos.
- **A validade é conferida pelo banco** (`NOW()`), e não comparando a data do banco com `time()` do PHP. Misturar os dois falha quando o banco e o PHP estão em fusos diferentes: o código correto aparecia como expirado.
- Quem já tem 2FA e fica sem e-mail disponível não consegue entrar. Para liberar uma conta: `UPDATE usuarios SET dois_fatores_ativo = 0 WHERE email = '...';`.

## Web Push

O navegador só solicita a inscrição quando `PUSH_VAPID_PUBLIC_KEY` está preenchida e o usuário está autenticado. A assinatura fica em `push_subscriptions`. O envio usa `minishlink/web-push` (já instalado em `vendor`), que precisa das extensões `openssl`, `curl`, `mbstring` e `gmp` ou `bcmath`.

**Gerar as chaves** (uma vez por ambiente; guarde o par, pois trocar a chave obriga quem já ativou a ativar de novo):

```powershell
$env:OPENSSL_CONF="C:\xampp\php\extras\ssl\openssl.cnf"
php scripts/gerar_vapid.php --arquivo
```

- No Windows/XAMPP, a variável `OPENSSL_CONF` precisa existir **antes** de o PHP iniciar, na mesma janela do terminal. Sem ela: "Unable to create the key".
- `--arquivo` grava em `storage/vapid_novas.txt` (ignorado pelo Git). Copie as linhas **inteiras** de lá para o `.env` e **apague o arquivo**, porque ele tem a chave privada. Copiar linhas longas do terminal pode cortá-las.
- Tamanhos válidos: chave pública **87** caracteres e privada **43**. Valores menores indicam chave cortada, e o navegador recusa o registro.

```text
PUSH_VAPID_PUBLIC_KEY=...
PUSH_VAPID_PRIVATE_KEY=...
PUSH_VAPID_SUBJECT=mailto:seuemail@exemplo.com
```

`PUSH_VAPID_SUBJECT` é só um contato para o serviço de push: use um e-mail real com `mailto:` ou o endereço do site. Não há confirmação nem relação com o login.

**Ativar no aparelho:** na página **Notificações** aparece **🔔 Ativar notificações** (o pedido de permissão só roda a partir do clique, exigência do iOS). Quem já autorizou se registra sozinho. No iPhone só funciona com o app **instalado na tela inicial**; exige HTTPS.

**Testar:** `php scripts/testar_push.php ID_DO_USUARIO` cria uma notificação (sino e push) para o usuário. Se o aviso não chegar, procure `[PushNotifier]` em `storage/logs/php_errors.log`. Se a permissão do site estiver "bloqueada" no navegador, o botão não aparece: libere nas configurações do site.

## Hospedagem no InfinityFree

Resumo do que foi aprendido ao publicar lá:

- **`putenv()` desativado:** o `.env` só funciona por `Env::get()` (ver "Variáveis de ambiente"). Foi a causa de e-mail e push "não funcionarem" mesmo com tudo configurado.
- **SMTP externo:** só a porta **587** é permitida. PHP `mail()` não serve.
- **Envio dos arquivos:** use FTP (FileZilla) arrastando **pastas inteiras** com sobrescrita. Evite subir arquivo por arquivo no gerenciador web (é fácil errar a pasta) e cuidado com zips que carregam a pasta do projeto dentro (extração duplicada).
- **Não enviar:** `.env`, `uploads/`, `logs/`, `storage/`, pastas de teste e diagnóstico.
- **`.env` do servidor** é editado à mão e nunca sobrescrito. O gerenciador web pode dar "Error saving file": baixe, edite no computador e envie de volta.
- **`.htaccess`:** precisa estar no servidor. Bloqueia `app`, `config`, `storage`, `vendor`, `database`, `logs`, `scripts`, `tests` e `docs`. Confirme abrindo `/.env` e `/logs/emails.log` no navegador: devem dar 403 ou 404.
- **Cache:** o `sw.js` guarda arquivos de `assets/`. Ao alterar JavaScript ou CSS, mude a versão do cache (`VERSION` em `sw.js`) e/ou o `?v=` do endereço do arquivo.
- **Conferir o que está no ar:** depois de enviar, confira o tamanho do arquivo no servidor contra o local, ou procure no arquivo um trecho novo.

## Solução de problemas

| Sintoma | Causa provável |
| --- | --- |
| E-mail não chega | Linha `Falha SMTP EcoSistemPet` em `storage/logs/php_errors.log`; `EMAIL_ENVIO` em `0`; senha de app com espaço; remetente diferente de `SMTP_USERNAME`. |
| Login mostra "Fale com o suporte" | `Mailer::disponivel()` falso: `EMAIL_ENVIO` não é `1` para o PHP (variável repetida no `.env` ou `.env` não lido). |
| Botão "Ativar notificações" não aparece | Chave VAPID vazia ou repetida no `.env`; permissão do site bloqueada; navegador sem suporte (iPhone sem o app instalado). |
| "Unable to create the key" | Windows sem `OPENSSL_CONF` na mesma janela do terminal. |
| Código 2FA "inválido" com o código certo | Comparação de validade misturando fusos (corrigida: validade conferida pelo banco); código antigo após um reenvio; limite de tentativas atingido. |
| Variável do `.env` "não pega" | Mais de uma linha com o mesmo nome (vale a primeira) ou `getenv()` usado no lugar de `Env::get()`. |

## Layout responsivo

Regras para as telas internas funcionarem do celular (360-440 px) ao desktop sem barra de rolagem horizontal:

- Largura mínima fixa em bloco flexível: use `min-width: min(320px, 100%)`, nunca `min-width: 320px`.
- Linhas `display:flex` com campo e botão: acrescente `flex-wrap: wrap`. Grupos de abas (`btn-group`) já quebram de linha no celular por regra global.
- Tabelas: use `class="tabela-pets tabela-cartao-mobile"`, `data-col="nome|foto|acoes"` na célula principal e `data-th="Rótulo"` nas demais. No celular cada linha vira um cartão; no desktop nada muda.
- O conteúdo principal (`.conteudo`, `main.container`) usa `width:auto` para respeitar a barra lateral de 240 px em notebooks de 1280-1366 px.

Para conferir, abra a tela no DevTools em 440 px, 360 px e 1280 px: a página não deve ter rolagem horizontal. 320 px (aparelhos muito antigos) é atendido em melhor esforço.

## Onboarding

`public/conta/onboarding.php` apresenta os primeiros passos para tutor ou empresa e registra a conclusão no perfil correspondente.

## Métricas

Visualizações de páginas públicas são registradas em `metricas_empresa_eventos` e resumidas no `painel_b2b.php`. Os CTAs de produto/empresa registram cliques e pedidos confirmados registram conversões por empresa.

## Rastreamento

Pedidos guardam transportadora, código de rastreio e histórico em `pedido_status_historico`. A atualização de status deve ser feita pelo fluxo administrativo/integração da transportadora; o cliente consulta o histórico em `pedido_confirmado.php`.

## Testes E2E

Instale as dependências JavaScript e o Chromium:

```powershell
npm install
npx playwright install chromium
```

Configure `E2E_BASE_URL`, `E2E_EMAIL`, `E2E_PASSWORD`, `E2E_PET_ID` e `E2E_PRODUTO_ID` com registros reais de teste. Execute `npm run test:e2e`. O fluxo usa login, solicitação de adoção e checkout, sem credenciais versionadas.
