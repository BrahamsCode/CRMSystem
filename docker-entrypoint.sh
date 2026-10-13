#!/bin/bash
set -e

# Esperar a que la base de datos esté lista
echo "Esperando a que PostgreSQL esté listo..."
until pg_isready -h ${DB_HOST:-db} -p ${DB_PORT:-5432} -U ${DB_USERNAME:-root}; do
  echo "PostgreSQL no está listo - esperando..."
  sleep 2
done

echo "PostgreSQL está listo!"

# Verificar que existe el archivo .env
if [ ! -f .env ]; then
    echo "Creando archivo .env desde .env.example..."
    cp .env.example .env
fi

# Generar APP_KEY si no existe
if ! grep -q "APP_KEY=base64:" .env; then
    echo "Generando APP_KEY..."
    php artisan key:generate --force
fi

# Optimizar configuración de Laravel para producción
echo "Optimizando Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ejecutar migraciones
echo "Ejecutando migraciones..."
php artisan migrate --force

# Ejecutar seeders si es necesario (descomenta si quieres ejecutar seeders)
# php artisan db:seed --force

# Limpiar caché
php artisan cache:clear

echo "Aplicación lista!"

# Ejecutar el comando principal (Apache)
exec "$@"
