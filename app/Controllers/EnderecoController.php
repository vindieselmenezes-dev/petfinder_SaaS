<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Controller: EnderecoController
 * ==========================================================
 */

require_once __DIR__ . '/../Models/Endereco.php';

class EnderecoController
{
    /**
     * Model
     */
    private Endereco $endereco;

    /**
     * Construtor
     */
    public function __construct()
    {
        $this->endereco = new Endereco();
    }

    /**
     * Busca o endereço do usuário
     */
    public function buscarPorUsuario(int $usuarioId): ?array
    {
        return $this->endereco->buscarPorUsuario($usuarioId);
    }

    /**
     * Salva (cria ou atualiza) o endereço do usuário
     */
    public function salvar(array $dados): bool
    {
        /*
        |--------------------------------------------------------------
        | Validações obrigatórias
        |--------------------------------------------------------------
        */

        if (empty($dados["usuario_id"])) {
            return false;
        }

        if (empty($dados["cidade"])) {
            return false;
        }

        if (empty($dados["estado"])) {
            return false;
        }

        if (strlen($dados["estado"]) !== 2) {
            return false;
        }

        /*
        |--------------------------------------------------------------
        | Valores padrão
        |--------------------------------------------------------------
        */

        $dados["cep"]         = $dados["cep"] ?? "";
        $dados["logradouro"]  = $dados["logradouro"] ?? "";
        $dados["numero"]      = $dados["numero"] ?? "";
        $dados["complemento"] = $dados["complemento"] ?? "";
        $dados["bairro"]      = $dados["bairro"] ?? "";

        /*
        |--------------------------------------------------------------
        | Salva no banco
        |--------------------------------------------------------------
        */

        return $this->endereco->salvar($dados);
    }
}
