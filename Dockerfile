# syntax=docker/dockerfile:1

# Imagen base con PHP 8.3 CLI sobre Debian
FROM php:8.3-cli-bullseye

# 1) Instalar dependencias del sistema + extensiones necesarias
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libzip-dev \
    libpq-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

# 2) Instalar Node.js 20 (para build de Vite)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get update && apt-get install -y nodejs \
    && npm install -g npm@latest \
    && rm -rf /var/lib/apt/lists/*

# 3) Instalar Composer copiándolo desde la imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 4) Directorio de trabajo dentro del contenedor
WORKDIR /var/www/html

# 5) Copiar archivos de definición de dependencias primero (para caché)
COPY composer.json composer.lock ./

# ⛔ IMPORTANTE: instalar dependencias PHP SIN scripts (para que no llame a "php artisan")
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress --no-scripts

# 6) Copiar archivos de frontend para instalar dependencias JS
COPY package.json package-lock.json* vite.config.* ./

# Instalar dependencias de Node solo si existe package.json
RUN if [ -f package.json ]; then npm ci; fi

# 7) Copiar TODO el proyecto (incluye artisan, app/, config/, resources/, etc.)
COPY . .

# 8) Compilar assets con Vite (si existe package.json)
RUN if [ -f package.json ]; then npm run build; fi

# 9) Ajustar permisos mínimos para storage y cache
RUN mkdir -p storage/framework/cache \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# 10) Exponer puerto interno (Render usará la variable $PORT igualmente)
EXPOSE 8000

# 11) Comando de arranque:
#     - Ejecuta package:discover ahora que el código ya está presente
#     - Limpia y genera caches
#     - Levanta el servidor Laravel escuchando en $PORT
CMD ["sh", "-c", "\
    php artisan package:discover --ansi || true && \
    php artisan config:clear && \
    php artisan route:clear && \
    php artisan view:clear && \
    php artisan config:cache || true && \
    php artisan view:cache || true && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-8000} \
"]

