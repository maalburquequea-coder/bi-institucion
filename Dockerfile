FROM php:8.2-apache

# Extensiones necesarias
RUN apt-get update && apt-get install -y \
        libzip-dev libonig-dev libssl-dev curl unzip \
    && docker-php-ext-install pdo pdo_mysql zip mbstring \
    && a2enmod rewrite headers

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar proyecto
COPY . .

# Instalar dependencias PHP (sin dev)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Directorio de uploads con permisos
RUN mkdir -p uploads/asistencia \
    && chown -R www-data:www-data uploads/ \
    && chmod -R 755 uploads/

# Configurar Apache: AllowOverride para .htaccess + DirectoryIndex con I mayúscula
RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf \
    && sed -i 's|DirectoryIndex index.html|DirectoryIndex Index.php index.php index.html|g' /etc/apache2/mods-enabled/dir.conf

# Script de arranque (crea .env desde variables de entorno de Render)
COPY docker-entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

CMD ["/usr/local/bin/entrypoint.sh"]
