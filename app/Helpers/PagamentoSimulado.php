<?php

declare(strict_types=1);

require_once __DIR__ . '/../Core/Env.php';
require_once __DIR__ . '/../Core/SegurancaHttp.php';

/**
 * ==========================================================
 * PETFINDER BRASIL
 * Helper: PagamentoSimulado
 * ==========================================================
 * O site ainda não tem gateway de pagamento real. Enquanto isso,
 * o "pagamento" e a troca de plano são SIMULADOS (aprovam na hora).
 * Isso é ótimo para desenvolvimento e demonstração, mas num site
 * público qualquer pessoa conseguiria usar planos pagos e comprar
 * sem pagar nada.
 *
 * Esta trava deixa a simulação ligada só quando:
 *   - o ambiente é local (APP_ENV=local); ou
 *   - o .env tem PAGAMENTO_SIMULADO=true (modo demonstração, de
 *     propósito); ou
 *   - quem pede é administrador / suporte técnico (ações manuais).
 *
 * Fora disso (produção), a regra é "falha segura": nada é aprovado
 * sem pagamento real.
 */
final class PagamentoSimulado
{
    private function __construct()
    {
        // Classe utilitária (apenas métodos estáticos): não deve ser instanciada.
    }

    /**
     * Modo demonstração: pedidos e planos são aprovados sem cobrança real.
     */
    public static function habilitado(): bool
    {
        return SegurancaHttp::ehAmbienteLocal() || Env::get('PAGAMENTO_SIMULADO') === 'true';
    }

    /**
     * Quem pode acionar troca de plano / mudança de faturamento sem cobrança.
     *
     * @param int $empresaId Empresa em questão (0 = qualquer). No modo suporte,
     *                       só vale para a empresa que está sendo atendida.
     */
    public static function usuarioPodeSimular(int $empresaId = 0): bool
    {
        if (self::habilitado()) {
            return true;
        }

        if (Auth::check() && Auth::ehAdministrador()) {
            return true;
        }

        if (Auth::emModoSuporte()) {
            return $empresaId <= 0
                || (int) ($_SESSION['impersonated_empresa_id'] ?? 0) === $empresaId;
        }

        return false;
    }

    public static function mensagemIndisponivel(): string
    {
        return 'A contratação online de planos ainda não está disponível. '
            . 'Seu período de teste continua valendo. Para contratar ou alterar seu plano, fale com o suporte.';
    }
}
