#!/bin/sh
# Solo se ejecuta la primera vez que arranca PostgreSQL con el volumen vacío.
# Crea la base de los tests (phpunit.xml → <POSTGRES_DB>_testing).
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-SQL
    CREATE DATABASE "${POSTGRES_DB}_testing" OWNER "$POSTGRES_USER";
SQL
