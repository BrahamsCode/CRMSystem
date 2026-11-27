#!/usr/bin/env bash
# Ejecuta un comando de PHP 8.5 dentro de un contenedor efímero con el public_html DE ESTE worktree.
# Cada sesión (CRMSystem, CRMSystem-clientes, …) usa su propia BD de desarrollo y de pruebas,
# así las migraciones de una no rompen a otra. Requiere el stack levantado (INICIAR-CRM.bat o docker compose up -d).
#
# Uso (desde cualquier carpeta del worktree):
#   bash scripts/en-docker.sh php artisan test
#   bash scripts/en-docker.sh php artisan migrate --seed
#   bash scripts/en-docker.sh composer install
set -euo pipefail

ROOT="$(git rev-parse --show-toplevel)"
NOMBRE="$(basename "$ROOT")"                                   # CRMSystem, CRMSystem-clientes…
SUFIJO="$(printf '%s' "${NOMBRE#CRMSystem}" | tr -d '-' | tr 'A-Z' 'a-z')"
[ -z "$SUFIJO" ] && SUFIJO="main"

PROYECTO="crmsystem2"
IMAGEN="${PROYECTO}-app"
RED="${PROYECTO}_default"
DB_CONT="${PROYECTO}-db-1"
DB_USER="$(grep -E '^POSTGRES_USER=' "$(dirname "$0")/../docker/.env" 2>/dev/null | cut -d= -f2 || true)"
DB_USER="${DB_USER:-root}"
DB_DEV="${PROYECTO}_${SUFIJO}"
DB_TEST="${PROYECTO}_testing_${SUFIJO}"
# La BD de la sesión principal sigue siendo la del .env (crmsystem2)
[ "$SUFIJO" = "main" ] && DB_DEV="$PROYECTO" && DB_TEST="${PROYECTO}_testing"

if ! docker ps --format '{{.Names}}' | grep -qx "$DB_CONT"; then
  echo "El contenedor $DB_CONT no está corriendo. Levanta el stack: cd docker && docker compose up -d" >&2
  exit 1
fi
if ! docker image inspect "$IMAGEN" >/dev/null 2>&1; then
  echo "Falta la imagen $IMAGEN. Constrúyela: cd docker && docker compose build app" >&2
  exit 1
fi

# Crea las BD de esta sesión si faltan
for db in "$DB_DEV" "$DB_TEST"; do
  if ! docker exec "$DB_CONT" psql -U "$DB_USER" -d postgres -tAc "SELECT 1 FROM pg_database WHERE datname='$db'" | grep -q 1; then
    docker exec "$DB_CONT" createdb -U "$DB_USER" "$db"
    echo "BD creada: $db"
  fi
done

# .env del worktree: copia del principal (no está en git)
PRINCIPAL="$(dirname "$(git rev-parse --path-format=absolute --git-common-dir)")"
if [ ! -f "$ROOT/public_html/.env" ] && [ -f "$PRINCIPAL/public_html/.env" ]; then
  cp "$PRINCIPAL/public_html/.env" "$ROOT/public_html/.env"
  echo ".env copiado desde $PRINCIPAL/public_html"
fi

# Las pruebas usan la BD de pruebas de la sesión (phpunit.xml no la fuerza); el resto, la de desarrollo
DB_USO="$DB_DEV"
case "$*" in
  *"artisan test"*|*pest*|*phpunit*) DB_USO="$DB_TEST" ;;
esac

TTY_FLAGS="-i"
[ -t 0 ] && [ -t 1 ] && TTY_FLAGS="-it"

# vendor/ propio de cada worktree (en su public_html, fuera de git)
if [ ! -f "$ROOT/public_html/vendor/autoload.php" ] && [ "${1:-}" != "composer" ]; then
  echo "Instalando dependencias de Composer en este worktree…"
  MSYS_NO_PATHCONV=1 docker run --rm -i --network "$RED" -v "$ROOT/public_html:/var/www" -w /var/www "$IMAGEN" composer install --no-interaction --no-progress
fi

MSYS_NO_PATHCONV=1 exec docker run --rm $TTY_FLAGS \
  --network "$RED" \
  -v "$ROOT/public_html:/var/www" -w /var/www \
  -e DB_HOST=db -e DB_PORT=5432 -e DB_DATABASE="$DB_USO" \
  -e MAIL_HOST=mailpit -e MAIL_PORT=1025 \
  "$IMAGEN" "$@"
