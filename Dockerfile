FROM php:8.2-apache

# Extensiones necesarias
RUN apt-get update && apt-get install -y \
        libzip-dev libonig-dev libssl-dev curl unzip \
    && docker-php-ext-install pdo pdo_mysql zip mbstring \
    && a2enmod rewrite headers access_compat

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar proyecto
COPY . .

# Instalar dependencias PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Directorio de uploads
RUN mkdir -p uploads/asistencia \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 uploads/

# Configuración Apache
COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Script de arranque
COPY docker-entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

CMD ["/usr/local/bin/entrypoint.sh"]
