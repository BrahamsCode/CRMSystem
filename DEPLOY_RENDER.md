# Guía de Deployment en Render

## Requisitos previos
- Cuenta en [Render](https://render.com)
- Repositorio en GitHub con el código del proyecto
- Git instalado localmente

## Archivos de configuración creados

Se han creado los siguientes archivos para el deployment:

1. **`Dockerfile`** - Imagen Docker optimizada para producción
2. **`docker-entrypoint.sh`** - Script de inicialización
3. **`render.yaml`** - Configuración de Blueprint para Render
4. **`.dockerignore`** - Archivos a ignorar en el build Docker

## Pasos para deployar

### Opción 1: Deployment automático con Blueprint (Recomendado)

1. **Hacer commit y push de los archivos**
   ```bash
   git add Dockerfile docker-entrypoint.sh render.yaml .dockerignore DEPLOY_RENDER.md
   git commit -m "Agregar configuración para deployment en Render"
   git push origin brahamscode/modulo-promocion
   ```

2. **Ir a Render Dashboard**
   - Ve a https://dashboard.render.com
   - Click en "New +" → "Blueprint"

3. **Conectar repositorio**
   - Selecciona tu repositorio de GitHub
   - Render detectará automáticamente el archivo `render.yaml`
   - Click en "Apply"

4. **Render creará automáticamente**:
   - Un servicio web (crmsystem-web)
   - Una base de datos PostgreSQL (crmsystem-db)
   - Configurará todas las variables de entorno necesarias

### Opción 2: Deployment manual

Si prefieres configurar manualmente:

#### 1. Crear la base de datos PostgreSQL

1. En Render Dashboard, click "New +" → "PostgreSQL"
2. Configuración:
   - **Name**: `crmsystem-db`
   - **Database**: `crmsystem2`
   - **User**: `root`
   - **Region**: Oregon (o el más cercano)
   - **Plan**: Starter (gratis)
3. Click "Create Database"
4. **Guardar las credenciales** que Render te proporciona

#### 2. Crear el servicio web

1. En Render Dashboard, click "New +" → "Web Service"
2. Conecta tu repositorio de GitHub
3. Configuración:
   - **Name**: `crmsystem-web`
   - **Region**: Oregon (misma que la DB)
   - **Branch**: `brahamscode/modulo-promocion` (o tu rama principal)
   - **Environment**: Docker
   - **Plan**: Starter ($7/mes) o Free

4. **Variables de entorno** (Environment Variables):
   ```
   APP_NAME=CRM System
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://tu-app.onrender.com
   APP_KEY=                     # Render lo generará automáticamente
   
   APP_LOCALE=es
   APP_FALLBACK_LOCALE=en
   
   DB_CONNECTION=pgsql
   DB_HOST=                     # Copiar de las credenciales de PostgreSQL
   DB_PORT=5432
   DB_DATABASE=crmsystem2
   DB_USERNAME=root
   DB_PASSWORD=                 # Copiar de las credenciales de PostgreSQL
   
   SESSION_DRIVER=database
   CACHE_STORE=database
   QUEUE_CONNECTION=database
   
   MAIL_MAILER=log
   MAIL_FROM_ADDRESS=noreply@example.com
   MAIL_FROM_NAME=CRM System
   ```

5. Click "Create Web Service"

## Post-deployment

### 1. Generar APP_KEY (si no se generó automáticamente)

En el Shell de Render (desde el dashboard del servicio web):
```bash
php artisan key:generate --force
```

### 2. Ejecutar seeders (opcional)

Si necesitas datos iniciales:
```bash
php artisan db:seed --class=AdminSeeder --force
php artisan db:seed --class=CatalogSeeder --force
```

### 3. Verificar la aplicación

- Visita la URL que Render te proporciona (ej: `https://crmsystem-web.onrender.com`)
- Verifica que la página de login aparezca correctamente

## Comandos útiles

### Ver logs en tiempo real
Desde el dashboard de Render → Logs

### Conectarse a la base de datos
Usa las credenciales de "External Database URL" que Render proporciona:
```bash
psql <EXTERNAL_DATABASE_URL>
```

### Ejecutar comandos Artisan
Desde el Shell de Render:
```bash
php artisan migrate:status
php artisan cache:clear
php artisan config:clear
```

## Configuración de dominio personalizado

1. En Render Dashboard → tu servicio web → Settings → Custom Domain
2. Agrega tu dominio
3. Configura los registros DNS según las instrucciones de Render

## Troubleshooting

### La aplicación no inicia
- Revisa los logs en Render Dashboard
- Verifica que todas las variables de entorno estén configuradas
- Asegúrate de que `APP_KEY` esté generado

### Error de conexión a la base de datos
- Verifica que las credenciales de PostgreSQL sean correctas
- Asegúrate de que la base de datos y el servicio web estén en la misma región

### Errores de permisos
- Verifica que las carpetas `storage/` y `bootstrap/cache/` tengan permisos de escritura
- El Dockerfile ya configura estos permisos automáticamente

### Assets no cargan
- Verifica que `npm run build` se ejecute correctamente durante el build
- Revisa los logs de build en Render

## Actualizaciones automáticas

Render automáticamente rebuildeará y deployrá tu aplicación cada vez que hagas push a la rama configurada en GitHub.

Para deshabilitar esto:
- Settings → Build & Deploy → Auto-Deploy: Off

## Costos

- **PostgreSQL Starter**: Gratis (500 MB)
- **Web Service Starter**: $7/mes
- **Web Service Free**: Gratis (duerme después de 15 min de inactividad)

## Notas importantes

1. El plan gratuito de Render "duerme" después de 15 minutos de inactividad
2. El primer request después de dormir puede tardar 30-60 segundos
3. La zona horaria está configurada a `Asia/Tokyo` según tu configuración
4. Los logs se guardan por 7 días en el plan gratuito

## Soporte

- Documentación oficial: https://docs.render.com
- Community: https://community.render.com
