<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Model: Notificacao
 * ==========================================================
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Helpers/Mailer.php';
require_once __DIR__ . '/../Helpers/PushNotifier.php';
require_once __DIR__ . '/../Core/Url.php';

class Notificacao
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    /**
     * Transforma o link salvo na notificação em um endereço que realmente abre.
     *
     * O link vem de três formas: nome de página solta ("minhas_consultas.php",
     * com ou sem ?query), caminho já resolvido ("/public/...", vindo de
     * Url::pagina) ou endereço completo (https://...). Devolve um caminho do
     * site (começa com "/") ou o endereço completo; null se não houver link.
     */
    public static function resolverLink(?string $link): ?string
    {
        $link = trim((string) $link);

        if ($link === '') {
            return null;
        }

        if (preg_match('#^(https?:)?//#i', $link) || $link[0] === '/') {
            return $link;
        }

        // Separa a query para o Url::pagina achar o módulo pelo nome do arquivo.
        $partes = explode('?', $link, 2);
        $caminho = Url::pagina($partes[0]);

        return isset($partes[1]) ? $caminho . '?' . $partes[1] : $caminho;
    }

    /**
     * Endereço completo (com domínio), para usar dentro de e-mails.
     */
    private static function linkAbsoluto(?string $link): ?string
    {
        $resolvido = self::resolverLink($link);

        if ($resolvido === null || preg_match('#^(https?:)?//#i', $resolvido)) {
            return $resolvido;
        }

        $absoluto = Url::absoluta($resolvido);

        // Fora de uma requisição web não há HOST: usa a APP_URL do .env.
        if ($absoluto === $resolvido) {
            $appUrl = rtrim((string) (Env::get('APP_URL') ?: ''), '/');
            return $appUrl !== '' ? $appUrl . $resolvido : $resolvido;
        }

        return $absoluto;
    }

    /**
     * Cria uma nova notificação para um usuário.
     *
     * Toda notificação aparece no sino e dispara push. Ela só vira E-MAIL quando
     * quem a cria pede ($enviarEmail = true: eventos que a pessoa não pode
     * perder, como pedido, consulta, adoção ou resposta do suporte), ou quando
     * EMAIL_NOTIFICACOES=1 no .env força e-mail para todas.
     */
    public function criar(int $usuarioId, string $titulo, string $mensagem, string $tipo = 'Sistema', ?string $link = null, bool $enviarEmail = false): bool
    {
        $sql = "
            INSERT INTO notificacoes (usuario_id, titulo, mensagem, link, tipo, lida)
            VALUES (:usuario_id, :titulo, :mensagem, :link, :tipo, 0)
        ";

        $stmt = $this->pdo->prepare($sql);

        $criada = $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':titulo' => $titulo,
            ':mensagem' => $mensagem,
            ':link' => $link,
            ':tipo' => $tipo
        ]);

        if ($criada && ($enviarEmail || Env::get('EMAIL_NOTIFICACOES') === '1')) {
            $stmtUsuario = $this->pdo->prepare('SELECT email, nome FROM usuarios WHERE id = :id');
            $stmtUsuario->execute([':id' => $usuarioId]);
            $usuario = $stmtUsuario->fetch();

            if (!empty($usuario['email'])) {
                $nome = htmlspecialchars((string) ($usuario['nome'] ?? 'usuário'), ENT_QUOTES, 'UTF-8');
                $tituloSeguro = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
                $mensagemSegura = nl2br(htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'));
                $linkEmail = self::linkAbsoluto($link);
                $linkHtml = $linkEmail ? '<p><a href="' . htmlspecialchars($linkEmail, ENT_QUOTES, 'UTF-8') . '">Abrir no EcoSistemPet</a></p>' : '';
                Mailer::enviar(
                    (string) $usuario['email'],
                    'EcoSistemPet: ' . $titulo,
                    '<p>Olá, ' . $nome . '.</p><h2>' . $tituloSeguro . '</h2><p>' . $mensagemSegura . '</p>' . $linkHtml
                );
            }
        }

        if ($criada) {
            PushNotifier::enviar($usuarioId, $titulo, $mensagem, self::resolverLink($link));
        }

        return $criada;
    }

    /**
     * Lista as notificações de um usuário, mais recentes primeiro
     */
    public function listarPorUsuario(int $usuarioId, int $limite = 50): array
    {
        $sql = "
            SELECT id, titulo, mensagem, link, tipo, lida, criado_em
            FROM notificacoes
            WHERE usuario_id = :usuario_id
            ORDER BY criado_em DESC, id DESC
            LIMIT :limite
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Conta quantas notificações não lidas um usuário tem
     */
    public function contarNaoLidas(int $usuarioId): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM notificacoes
            WHERE usuario_id = :usuario_id
              AND lida = 0
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Marca uma notificação específica como lida (só se for do usuário)
     */
    public function marcarComoLida(int $id, int $usuarioId): bool
    {
        $sql = "
            UPDATE notificacoes
            SET lida = 1
            WHERE id = :id
              AND usuario_id = :usuario_id
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':usuario_id' => $usuarioId
        ]);
    }

    /**
     * Marca todas as notificações de um usuário como lidas
     */
    public function marcarTodasComoLidas(int $usuarioId): bool
    {
        $sql = "
            UPDATE notificacoes
            SET lida = 1
            WHERE usuario_id = :usuario_id
              AND lida = 0
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([':usuario_id' => $usuarioId]);
    }

    /**
     * Exclui uma notificação (só se for do usuário)
     */
    public function excluir(int $id, int $usuarioId): bool
    {
        $sql = "
            DELETE FROM notificacoes
            WHERE id = :id
              AND usuario_id = :usuario_id
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':usuario_id' => $usuarioId
        ]);
    }
}
