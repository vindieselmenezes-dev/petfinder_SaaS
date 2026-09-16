<?php
/**
 * ==========================================================
 * PETFINDER BRASIL
 * Arquivo: config/database.php
 * ==========================================================
 * As credenciais agora podem vir de variáveis de ambiente
 * (definidas no .env ou no servidor). Se não existirem, cai
 * nos valores padrão de desenvolvimento local — então nada
 * quebra em quem já está rodando o projeto localmente.
 */

declare(strict_types=1);

class Database
{
    private static ?PDO $connection = null;

    private static function config(string $chave, string $padrao): string
    {
        $valor = getenv($chave);
        return $valor !== false ? $valor : $padrao;
    }

    /**
     * Retorna uma conexão PDO (sempre a mesma instância)
     */
    public static function conectar(): PDO
    {
        if (self::$connection === null) {

            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                self::config('DB_HOST', 'localhost'),
                self::config('DB_DATABASE', 'petfinder'),
                self::config('DB_CHARSET', 'utf8mb4')
            );

            try {
                self::$connection = new PDO(
                    $dsn,
                    self::config('DB_USER', 'root'),
                    self::config('DB_PASSWORD', ''),
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $e) {
                error_log('Erro de conexão com o banco: ' . $e->getMessage());
                http_response_code(500);
                die('Não foi possível conectar ao banco de dados. Tente novamente em instantes.');
            }
        }

        return self::$connection;
    }
}
