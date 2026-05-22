FROM php:8.2-apache

# 1. Actualizar el sistema e instalar dependencias básicas/extensiones de PHP primero
RUN apt-get update && apt-get install -y \
    && docker-php-ext-install mysqli \
    && docker-php-ext-enable mysqli

# 2. Copiar los archivos de tu proyecto al contenedor
COPY . /var/www/html/

# 3. Exponer el puerto
EXPOSE 80
