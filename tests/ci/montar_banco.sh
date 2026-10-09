#!/usr/bin/env bash
# ============================================================
# Monta o banco de TESTE do zero a partir dos arquivos de database/
#
#   1. database/petfinder.sql        (estrutura base + dados iniciais)
#   2. database/migration_*.sql      (em ordem numérica)
#
# ATENÇÃO: este script APAGA e recria o banco "petfinder".
# Por segurança, só roda se CI_RECRIAR_BANCO=sim estiver definido
# (o GitHub Actions define isso sozinho; na sua máquina, não rode
# apontando para o banco de verdade).
#
# Variáveis lidas: DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE.
# ============================================================
set -u

HOST="${DB_HOST:-127.0.0.1}"
USUARIO="${DB_USER:-root}"
BANCO="${DB_DATABASE:-petfinder}"
export MYSQL_PWD="${DB_PASSWORD:-}"

if [ "${CI_RECRIAR_BANCO:-}" != "sim" ]; then
    echo "Este script APAGA o banco '$BANCO'. Para continuar, defina CI_RECRIAR_BANCO=sim." >&2
    exit 1
fi

if [ "$BANCO" != "petfinder" ]; then
    echo "Os arquivos de database/ usam 'USE petfinder'; defina DB_DATABASE=petfinder." >&2
    exit 1
fi

cd "$(dirname "$0")/../.." || exit 1

sql() { mysql -h "$HOST" -u "$USUARIO" --default-character-set=utf8mb4 "$@"; }

falhou=0

# Aplica um arquivo SQL. Ignora só os avisos que significam "isso já existe"
# (o petfinder.sql já traz as migrations mais antigas):
#   1050 tabela, 1060 coluna, 1061 índice e 1062 linha duplicada.
# Qualquer outro erro derruba o script.
aplicar() {
    local saida problemas
    saida=$(sql --force "$BANCO" < "$1" 2>&1)
    problemas=$(printf '%s\n' "$saida" | grep -E '^ERROR' | grep -vE '^ERROR (1050|1060|1061|1062) ' || true)
    if [ -n "$problemas" ]; then
        echo "ERRO ao aplicar $1:"
        echo "$problemas"
        falhou=1
    fi
}

echo "Recriando o banco '$BANCO'..."
sql -e "DROP DATABASE IF EXISTS \`$BANCO\`; CREATE DATABASE \`$BANCO\` CHARACTER SET utf8mb4;" || exit 1

echo "Aplicando database/petfinder.sql..."
aplicar database/petfinder.sql

for arquivo in $(ls database/migration_[0-9]*.sql | sort); do
    echo "Aplicando $arquivo..."
    aplicar "$arquivo"
done

# Conferência final: a estrutura existe e os dados de referência foram semeados
planos=$(sql -N "$BANCO" -e "SELECT COUNT(*) FROM planos" 2>/dev/null || echo 0)
tabelas=$(sql -N "$BANCO" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$BANCO' AND table_type='BASE TABLE'" 2>/dev/null || echo 0)
echo "Tabelas criadas: $tabelas | planos semeados: $planos"

if [ "${planos:-0}" -lt 3 ] || [ "${tabelas:-0}" -lt 60 ]; then
    echo "ERRO: o banco não ficou completo (esperado: ao menos 60 tabelas e 3 planos)."
    falhou=1
fi

exit "$falhou"
