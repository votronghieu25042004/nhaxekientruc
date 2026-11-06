# Multi-stage build for Laravel + Vue 3 (Inertia)
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM php:8.2-cli-alpine
WORKDIR /var/www/html

# Install system dependencies & PHP extensions
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    oniguruma-dev \
    sqlite-dev \
    ca-certificates

RUN docker-php-ext-install pdo pdo_mysql pdo_sqlite bcmath mbstring opcache

# Copy Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy code
COPY . .
COPY --from=frontend /app/public/build ./public/build

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions
RUN chmod -R 777 storage bootstrap/cache

# Expose Render default port
EXPOSE 10000

# Start Laravel
CMD php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=10000
