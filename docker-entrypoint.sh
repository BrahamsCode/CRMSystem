#!/bin/bash
# Arranque de la imagen de PRODUCCIÓN (Dockerfile de la raíz). Doc/contratos/DESPLIEGUE.md §2.
# La configuración sale SOLO de las variables del servicio: no hay .env en la imagen ni se crea aquí.
# No corre migraciones: la BD es la del legacy (CONVENCIONES §0) y su esquema se prepara a mano.
#
# Variables propias de este script:
#   DB_WAIT_SECONDS           segundos máximos de espera a PostgreSQL (60)
#   CRM_SCHEDULER_ENABLED     true = `schedule:work` en este contenedor (por defecto true; false si otra
#                             instancia ya lo corre: las tareas no deben correr dos veces)
#   CRM_QUEUE_WORKER_ENABLED  true = `queue:work` en este contenedor (por defecto false: QUEUE_CONNECTION=sync)
set -e
cd /var/www

como_web() {
  runuser -u www-data -- "$@"
}

# APP_KEY fija: generarla aquí cambiaría la clave en cada arranque y cerraría todas las sesiones
if [ -z "${APP_KEY:-}" ]; then
  echo "Falta APP_KEY en las variables del servicio (php artisan key:generate --show)." >&2
  exit 1
fi

# Esperar a PostgreSQL, con límite (si no llega, Apache arranca igual y el error se ve en el log)
espera="${DB_WAIT_SECONDS:-60}"
until pg_isready -q -h "${DB_HOST:-127.0.0.1}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-root}"; do
  espera=$((espera - 2))
  if [ "$espera" -le 0 ]; then
    echo "PostgreSQL no responde en ${DB_HOST:-127.0.0.1}:${DB_PORT:-5432}; se sigue sin esperar más." >&2
    break
  fi
  sleep 2
done

# storage/ puede ser un disco montado (vacío o de root): estructura y dueño en cada arranque
mkdir -p storage/app/public storage/app/private storage/framework/cache/data storage/framework/sessions \
  storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# public/storage → storage/app/public (asset('storage/…'))
php artisan storage:link --force >/dev/null

# Cachés de config, rutas, vistas y eventos (solo en producción; nunca en el docker local)
como_web php artisan optimize:clear >/dev/null
como_web php artisan optimize

# Procesos en segundo plano, con reinicio si terminan (como www-data, para que lo que escriban en storage/ sea de Apache)
en_bucle() {
  while true; do
    como_web "$@" || true
    echo "$* terminó; se reinicia en 5 s" >&2
    sleep 5
  done
}

if [ "${CRM_SCHEDULER_ENABLED:-true}" = "true" ]; then
  echo "Planificador: php artisan schedule:work"
  en_bucle php artisan schedule:work &
fi

if [ "${CRM_QUEUE_WORKER_ENABLED:-false}" = "true" ]; then
  echo "Cola: php artisan queue:work"
  en_bucle php artisan queue:work --tries=1 --sleep=3 --max-time=3600 &
fi

echo "Aplicación lista."

# Apache en primer plano
exec "$@"
