# Dockerfile para Sistema Admin EEST2
# Multi-stage build para optimización de seguridad y tamaño

# Stage 1: Build stage
FROM php:8.2-fpm-alpine AS builder

# Instalar dependencias del sistema
RUN apk add --no-cache \
    git \
    curl \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        zip \
        gd \
        opcache \
        bcmath

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar archivos del proyecto
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Stage 2: Production stage
FROM php:8.2-fpm-alpine AS production

# Crear usuario no-root para seguridad
RUN addgroup -g 1000 -S www && \
    adduser -u 1000 -D -S -G www www

# Instalar dependencias mínimas necesarias
RUN apk add --no-cache \
    libzip \
    libpng \
    libjpeg-turbo \
    freetype \
    oniguruma \
    mysql-client \
    nginx \
    supervisor \
    tzdata \
    && rm -rf /var/cache/apk/*

# Configurar PHP
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Instalar extensiones PHP necesarias
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        zip \
        gd \
        opcache \
        bcmath

# Configurar OPcache para producción
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Copiar aplicación desde builder
COPY --from=builder --chown=www:www /var/www/html/vendor /var/www/html/vendor
COPY --chown=www:www . /var/www/html

# Configurar permisos de seguridad
RUN chown -R www:www /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/logs \
    && chmod -R 777 /var/www/html/backups \
    && find /var/www/html -type f -name "*.php" -exec chmod 644 {} \; \
    && find /var/www/html -type d -exec chmod 755 {} \;

# Configurar Nginx
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Configurar Supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Crear directorios necesarios
RUN mkdir -p /var/log/nginx \
    && mkdir -p /var/log/supervisor \
    && mkdir -p /run/nginx \
    && touch /var/log/nginx/access.log \
    && touch /var/log/nginx/error.log

# Health check
COPY docker/health-check.sh /usr/local/bin/health-check.sh
RUN chmod +x /usr/local/bin/health-check.sh

# Exponer puerto
EXPOSE 80

# Cambiar a usuario no-root
USER www

# Comando de inicio
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD /usr/local/bin/health-check.sh
