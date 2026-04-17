FROM php:8.2-cli

# Instalar extensiones necesarias
RUN docker-php-ext-install pdo pdo_pgsql pgsql

# Copiar archivos del proyecto
WORKDIR /app
COPY . .

# Exponer puerto
EXPOSE 10000

# Ejecutar servidor PHP
CMD ["php", "-S", "0.0.0.0:10000"]