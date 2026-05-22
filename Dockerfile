FROM php:8.2-apache

# 1. Instalar utilidades del sistema y extensión mysqli
RUN apt-get update && apt-get install -y \
    zip \
    unzip \
    git \
    && docker-php-ext-install mysqli \
    && docker-php-ext-enable mysqli

# 2. Instalar Composer oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Directorio de trabajo
WORKDIR /var/www/html

# 4. Copiar archivos del proyecto
COPY . /var/www/html/

# 5. Instalar dependencias de Composer
RUN if [ ! -f "composer.json" ]; then \
        echo '{"require": {"google/cloud-storage": "^1.42"}}' > composer.json; \
    fi && composer install --no-dev --optimize-autoloader

# ============================================================
# SOLUCIÓN DE SEGURIDAD PARA APACHE Y GOOGLE STORAGE
# ============================================================

# Forzamos a Apache a inyectar la variable de entorno de forma global
# Así la librería de Google la leerá automáticamente sin usar putenv() en PHP
RUN echo "SetEnv GOOGLE_APPLICATION_CREDENTIALS /var/www/html/proyecto-para-almacenamiento-ff8cd1f8f073.json" >> /etc/apache2/apache2.conf

EXPOSE 80
