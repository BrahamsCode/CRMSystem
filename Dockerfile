# syntax=docker/dockerfile:1
# Dockerfile optimizado para producción en Render
FROM php:8.2-apache

# Copiar Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Copiar Node.js 20 para compilar assets
COPY --from=node:20-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:20-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# Instalar extensiones PHP y dependencias del sistema
RUN --mount=type=bind,from=mlocati/php-extension-installer:2,source=/usr/bin/install-php-extensions,target=/usr/local/bin/install-php-extensions \
    install-php-extensions pdo_pgsql pgsql pdo_mysql intl zip gd opcache pcntl exif bcmath imagick \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        vim git curl zip unzip \
        postgresql-client \
        imagemagick \
        jpegoptim optipng pngquant gifsicle \
        tzdata \
    && ln -snf /usr/share/zoneinfo/Asia/Tokyo /etc/localtime \
    && echo "Asia/Tokyo" > /etc/timezone \
    && a2enmod rewrite headers \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/*

# Configuración de zona horaria y Apache
ENV TZ=Asia/Tokyo \
    APACHE_DOCUMENT_ROOT=/var/www/public

# Configurar DocumentRoot de Apache para Laravel
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Establecer directorio de trabajo
WORKDIR /var/www

# Copiar archivos del proyecto
COPY public_html /var/www

# Copiar configuración personalizada de PHP
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini

# Instalar dependencias de Composer (optimizado para producción)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Instalar dependencias de npm y compilar assets
RUN npm ci --only=production \
    && npm run build \
    && rm -rf node_modules

# Establecer permisos correctos
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Exponer puerto 80
EXPOSE 80

# Script de inicio
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
