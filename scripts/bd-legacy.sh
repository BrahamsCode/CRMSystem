#!/usr/bin/env bash
# Carga los DATOS del legacy (crm2019.backup) en una BD cuyo esquema ya creó Laravel.
# El esquema sale de public_html/database/schema/pgsql-schema.sql (lo carga `php artisan migrate`).
# Si tb_shop ya tiene filas, no hace nada (salvo con --forzar, que vacía y recarga las tablas del legacy).
#
# Uso:
#   bash scripts/en-docker.sh php artisan migrate      # primero el esquema
#   bash scripts/bd-legacy.sh crmsystem2               # BD de la web (CRMSystem/)
#   bash scripts/bd-legacy.sh crmsystem2_clientes      # BD de una sesión
#
# Backup: CRM_LEGACY_BACKUP o, por defecto, ../crm2-php8/db/crm2019.backup (junto al repo principal).
# Ojo: crm2019.backup trae el esquema completo pero casi sin datos (sin clientes ni reservas;
# el 98 % son códigos postales). Para datos reales hace falta un volcado de producción.
set -euo pipefail
export MSYS_NO_PATHCONV=1

DB="${1:?Falta el nombre de la BD}"
FORZAR="${2:-}"
DB_CONT="crmsystem2-db-1"
RAIZ="$(cd "$(dirname "$0")/.." && (pwd -W 2>/dev/null || pwd))"
PRINCIPAL="$(dirname "$(git -C "$RAIZ" rev-parse --path-format=absolute --git-common-dir)")"
BACKUP="${CRM_LEGACY_BACKUP:-$(dirname "$PRINCIPAL")/crm2-php8/db/crm2019.backup}"
DB_USER="$(grep -E '^POSTGRES_USER=' "$RAIZ/docker/.env" 2>/dev/null | cut -d= -f2 || true)"
DB_USER="${DB_USER:-root}"

psql_q() { docker exec "$DB_CONT" psql -U "$DB_USER" -d "$DB" -tAc "$1"; }

if ! psql_q "SELECT to_regclass('public.tb_customer') IS NOT NULL" | grep -q t; then
  echo "La BD $DB no tiene el esquema del legacy. Primero: bash scripts/en-docker.sh php artisan migrate" >&2
  exit 1
fi
if [ "$(psql_q 'SELECT count(*) FROM tb_shop')" != "0" ] && [ "$FORZAR" != "--forzar" ]; then
  echo "La BD $DB ya tiene datos del legacy (usa --forzar para recargar)."
  exit 0
fi
if [ ! -f "$BACKUP" ]; then
  echo "No encuentro el backup del legacy: $BACKUP (define CRM_LEGACY_BACKUP)" >&2
  exit 1
fi
if ! docker exec "$DB_CONT" test -f /tmp/crm2019.backup; then
  docker cp "$BACKUP" "$DB_CONT:/tmp/crm2019.backup"
fi

# Tablas del legacy = las que trae el backup (no se tocan las de Laravel)
TABLAS="$(docker exec "$DB_CONT" sh -c "pg_restore -l /tmp/crm2019.backup | awk '\$4==\"TABLE\" && \$5==\"public\" {print \$6}'")"
if [ "$FORZAR" = "--forzar" ]; then
  LISTA="$(printf '%s\n' $TABLAS | sed 's/.*/public."&"/' | paste -sd, -)"
  psql_q "TRUNCATE $LISTA RESTART IDENTITY CASCADE" >/dev/null
fi

echo "Cargando los datos del legacy en $DB…"
docker exec "$DB_CONT" pg_restore -U "$DB_USER" -d "$DB" --data-only --no-owner --no-acl --disable-triggers /tmp/crm2019.backup
echo "Listo: tb_shop=$(psql_q 'SELECT count(*) FROM tb_shop'), zip=$(psql_q 'SELECT count(*) FROM zip')"
