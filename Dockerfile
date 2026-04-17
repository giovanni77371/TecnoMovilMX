FROM php:8.2-cli

# Instalar dependencias necesarias para PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Copiar archivos del proyecto
WORKDIR /app
COPY . .

# Exponer puerto
EXPOSE 10000

# Ejecutar servidor PHP
CMD ["php", "-S", "0.0.0.0:10000"]
