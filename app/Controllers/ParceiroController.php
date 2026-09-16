<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Controller: ParceiroController
 * ==========================================================
 * Regras da área de parceiros (ONGs e empresas apoiadoras):
 * validação dos formulários e upload de logo/imagem de campanha.
 *
 * As páginas ficam só com a parte visual; tudo que decide se um
 * dado é aceitável mora aqui.
 */

class ParceiroController
{
    private Parceiro $parceiro;
    private Campanha $campanha;

    /** Extensões aceitas em logos e imagens de campanha */
    private const EXTENSOES_PERMITIDAS = ['jpg', 'jpeg', 'png', 'webp'];

    /** Tamanho máximo por imagem (5 MB) */
    private const TAMANHO_MAXIMO = 5 * 1024 * 1024;

    public function __construct()
    {
        $this->parceiro = new Parceiro();
        $this->campanha = new Campanha();
    }

    public function parceiros(): Parceiro
    {
        return $this->parceiro;
    }

    public function campanhas(): Campanha
    {
        return $this->campanha;
    }

    /**
     * Valida o formulário de candidatura/edição de parceiro.
     *
     * @return array<int, string> Lista de erros (vazia = tudo certo)
     */
    public function validarParceiro(array $dados): array
    {
        $erros = [];

        if (mb_strlen(trim($dados['nome'] ?? '')) < 3) {
            $erros[] = 'Informe o nome da ONG, projeto ou empresa (mínimo 3 caracteres).';
        }

        if (!isset(Parceiro::TIPOS[$dados['tipo'] ?? ''])) {
            $erros[] = 'Selecione o tipo de parceria.';
        }

        if (mb_strlen(trim($dados['descricao'] ?? '')) < 20) {
            $erros[] = 'Conte um pouco mais sobre o trabalho de vocês (mínimo 20 caracteres).';
        }

        $email = trim($dados['email_contato'] ?? '');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'O e-mail de contato informado não é válido.';
        }

        $site = trim($dados['site'] ?? '');
        if ($site !== '' && !filter_var($site, FILTER_VALIDATE_URL)) {
            $erros[] = 'O site deve começar com http:// ou https://.';
        }

        $linkDoacao = trim($dados['link_doacao'] ?? '');
        if ($linkDoacao !== '' && !filter_var($linkDoacao, FILTER_VALIDATE_URL)) {
            $erros[] = 'O link de doação deve começar com http:// ou https://.';
        }

        // Sem nenhum canal de contato, ninguém consegue ajudar o parceiro.
        $temContato = $email !== ''
            || trim($dados['whatsapp'] ?? '') !== ''
            || trim($dados['instagram'] ?? '') !== ''
            || $site !== '';

        if (!$temContato) {
            $erros[] = 'Informe pelo menos uma forma de contato (e-mail, WhatsApp, Instagram ou site).';
        }

        return $erros;
    }

    /**
     * Valida o formulário de campanha/evento/doação.
     *
     * @return array<int, string>
     */
    public function validarCampanha(array $dados): array
    {
        $erros = [];

        if (mb_strlen(trim($dados['titulo'] ?? '')) < 5) {
            $erros[] = 'O título precisa ter pelo menos 5 caracteres.';
        }

        if (!isset(Campanha::TIPOS[$dados['tipo'] ?? ''])) {
            $erros[] = 'Selecione se é campanha, evento ou doação.';
        }

        // Evento sem local e data vira só um texto solto — não ajuda ninguém.
        if (($dados['tipo'] ?? '') === 'evento') {
            if (trim($dados['local_evento'] ?? '') === '') {
                $erros[] = 'Informe o local do evento.';
            }
            if (trim($dados['data_inicio'] ?? '') === '') {
                $erros[] = 'Informe a data e hora de início do evento.';
            }
        }

        $meta = trim((string) ($dados['meta_valor'] ?? ''));
        if ($meta !== '' && (!is_numeric($meta) || (float) $meta < 0)) {
            $erros[] = 'A meta de arrecadação deve ser um valor numérico positivo.';
        }

        $inicio = trim($dados['data_inicio'] ?? '');
        $fim = trim($dados['data_fim'] ?? '');
        if ($inicio !== '' && $fim !== '' && strtotime($fim) < strtotime($inicio)) {
            $erros[] = 'A data de encerramento não pode ser anterior à data de início.';
        }

        $link = trim($dados['link_externo'] ?? '');
        if ($link !== '' && !filter_var($link, FILTER_VALIDATE_URL)) {
            $erros[] = 'O link externo deve começar com http:// ou https://.';
        }

        return $erros;
    }

    /**
     * Salva uma imagem enviada em /uploads/{$pasta} e devolve o nome do
     * arquivo gerado (ou null se não veio imagem / veio inválida).
     */
    public function processarImagem(array $arquivo, string $pasta, string $prefixo): ?string
    {
        if (empty($arquivo['name']) || ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

        if (!in_array($extensao, self::EXTENSOES_PERMITIDAS, true)) {
            return null;
        }

        $tamanho = (int) ($arquivo['size'] ?? 0);
        if ($tamanho <= 0 || $tamanho > self::TAMANHO_MAXIMO) {
            return null;
        }

        // Confere que o arquivo é mesmo uma imagem, e não um script
        // renomeado para .jpg.
        if (@getimagesize($arquivo['tmp_name']) === false) {
            return null;
        }

        $diretorio = dirname(__DIR__, 2) . '/uploads/' . $pasta;

        if (!is_dir($diretorio) && !mkdir($diretorio, 0777, true) && !is_dir($diretorio)) {
            return null;
        }

        $novoNome = uniqid($prefixo . '_', true) . '.' . $extensao;

        if (!ImagemUpload::salvar($arquivo['tmp_name'], $diretorio . '/' . $novoNome)) {
            return null;
        }

        return $novoNome;
    }

    /**
     * Normaliza os campos do formulário de parceiro vindos do POST.
     *
     * @return array<string, mixed>
     */
    public function dadosParceiroDoPost(array $post): array
    {
        return [
            'tipo'               => $post['tipo'] ?? 'ong',
            'nome'               => trim($post['nome'] ?? ''),
            'documento'          => preg_replace('/\D/', '', $post['documento'] ?? '') ?: '',
            'descricao'          => trim($post['descricao'] ?? ''),
            'como_ajuda'         => trim($post['como_ajuda'] ?? ''),
            'cidade'             => trim($post['cidade'] ?? ''),
            'estado'             => strtoupper(trim($post['estado'] ?? '')),
            'site'               => trim($post['site'] ?? ''),
            'instagram'          => ltrim(trim($post['instagram'] ?? ''), '@'),
            'whatsapp'           => trim($post['whatsapp'] ?? ''),
            'email_contato'      => trim($post['email_contato'] ?? ''),
            'chave_pix'          => trim($post['chave_pix'] ?? ''),
            'link_doacao'        => trim($post['link_doacao'] ?? ''),
            'aceita_voluntarios' => !empty($post['aceita_voluntarios']),
        ];
    }

    /**
     * Normaliza os campos do formulário de campanha vindos do POST.
     *
     * @return array<string, mixed>
     */
    public function dadosCampanhaDoPost(array $post): array
    {
        $meta = str_replace(',', '.', trim($post['meta_valor'] ?? ''));

        return [
            'tipo'            => $post['tipo'] ?? 'campanha',
            'titulo'          => trim($post['titulo'] ?? ''),
            'resumo'          => trim($post['resumo'] ?? ''),
            'descricao'       => trim($post['descricao'] ?? ''),
            'local_evento'    => trim($post['local_evento'] ?? ''),
            'data_inicio'     => $this->normalizarDataHora($post['data_inicio'] ?? ''),
            'data_fim'        => $this->normalizarDataHora($post['data_fim'] ?? ''),
            'meta_valor'      => $meta,
            'itens_desejados' => trim($post['itens_desejados'] ?? ''),
            'chave_pix'       => trim($post['chave_pix'] ?? ''),
            'link_externo'    => trim($post['link_externo'] ?? ''),
            'status'          => $post['status'] ?? 'ativa',
        ];
    }

    /**
     * Converte o valor de um <input type="datetime-local"> ou
     * <input type="date"> no formato que o MySQL espera.
     */
    private function normalizarDataHora(string $valor): string
    {
        $valor = trim($valor);

        if ($valor === '') {
            return '';
        }

        $timestamp = strtotime($valor);

        return $timestamp === false ? '' : date('Y-m-d H:i:s', $timestamp);
    }
}
