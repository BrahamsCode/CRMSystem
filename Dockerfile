# syntax=docker/dockerfile:1
# Imagen de PRODUCCIÓN (Render u otro hosting con contenedores). Doc/contratos/DESPLIEGUE.md.
# PHP 8.5 como docker/dockerfile (desarrollo usa FPM + Apache aparte; aquí, un solo contenedor con mod_php).
# Nada de esto lo usa el docker local (docker/docker-compose.yml) ni scripts/en-docker.sh.
FROM php:8.5-apache-trixie

# Copiar Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Copiar Node.js 24 para compilar assets (mismo que docker/dockerfile)
COPY --from=node:24-trixie-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:24-trixie-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# Instalar extensiones PHP y dependencias del sistema
# opcache ya viene compilado en el núcleo de PHP 8.5: no se instala aparte
# fonts-ipaexfont-*: IPAex (licencia IPA) para los PDF con nombres (髙, 﨑…), NUCLEO.md §11
RUN --mount=type=bind,from=mlocati/php-extension-installer:2,source=/usr/bin/install-php-extensions,target=/usr/local/bin/install-php-extensions \
    install-php-extensions pdo_pgsql pgsql pdo_mysql intl zip gd pcntl exif bcmath imagick \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        vim git curl zip unzip \
        postgresql-client \
        imagemagick \
        jpegoptim optipng pngquant gifsicle \
        fonts-ipaexfont-gothic fonts-ipaexfont-mincho \
        tzdata \
    && ln -snf /usr/share/zoneinfo/Asia/Tokyo /etc/localtime \
    && echo "Asia/Tokyo" > /etc/timezone \
    && a2enmod rewrite headers \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/*

# IPAex en una sola carpeta (config('crm.pdf.font_dir')), fuera de storage/ por si storage es un disco montado
RUN mkdir -p /usr/local/share/fonts/ipaex \
    && ln -s "$(find /usr/share/fonts -name ipaexg.ttf | head -n1)" /usr/local/share/fonts/ipaex/ipaexg.ttf \
    && ln -s "$(find /usr/share/fonts -name ipaexm.ttf | head -n1)" /usr/local/share/fonts/ipaex/ipaexm.ttf \
    && test -s /usr/local/share/fonts/ipaex/ipaexg.ttf && test -s /usr/local/share/fonts/ipaex/ipaexm.ttf

# Zona horaria y PDF (valores por defecto de la imagen; las variables del servicio los pisan)
ENV TZ=Asia/Tokyo \
    CRM_PDF_FONT_DIR=/usr/local/share/fonts/ipaex \
    CRM_PDF_DEFAULT_FONT=ipaexm

# Apache: vhost propio con AllowOverride All (public/.htaccess) y LimitRequestBody
COPY docker/produccion/000-default.conf /etc/apache2/sites-available/000-default.conf

# PHP de producción (sin display_errors, opcache sin revisar archivos)
COPY docker/produccion/php.ini /usr/local/etc/php/conf.d/zz-app.ini

# Política de ImageMagick (la misma que se monta en desarrollo)
COPY docker/policy.xml /etc/ImageMagick-7/policy.xml

# Establecer directorio de trabajo
WORKDIR /var/www

# Copiar archivos del proyecto (.dockerignore deja fuera .env, vendor, node_modules, subidas y pruebas)
COPY public_html /var/www

# Instalar dependencias de Composer (optimizado para producción)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Instalar dependencias de npm y compilar assets
# (Vite y Tailwind están en devDependencies: con --omit=dev el build fallaba)
RUN npm ci \
    && npm run build \
    && rm -rf node_modules

# El código queda de root; Apache (www-data) solo escribe en storage/ y bootstrap/cache/
RUN mkdir -p storage/app/public storage/app/private storage/framework/cache/data storage/framework/sessions \
        storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

# Exponer puerto 80
EXPOSE 80

# Script de inicio
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
