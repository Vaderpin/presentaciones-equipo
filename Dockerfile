FROM php:8.2-apache

# 1. Instalar utilidades necesarias para Composer y la extensión mysqli
RUN apt-get update && apt-get install -y \
    zip \
    unzip \
    git \
    && docker-php-ext-install mysqli \
    && docker-php-ext-enable mysqli

# 2. Descargar e instalar Composer oficial dentro del contenedor
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Establecer el directorio de trabajo antes de copiar los archivos
WORKDIR /var/www/html

# 4. Copiar todos los archivos de tu proyecto al contenedor
COPY . /var/www/html/

# 5. Inicializar Composer de forma automática si no existe un composer.json,
# o instalar las dependencias si es que ya tienes uno creado.
RUN if [ ! -f "composer.json" ]; then \
        echo '{"require": {}}' > composer.json; \
    fi && composer install --no-dev --optimize-autoloader
