FROM php:8.3-fpm

# Installa estensioni PHP necessarie per Laravel
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev \
    libpq-dev cron \
    && docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd

# Installa Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Copia la configurazione PHP personalizzata per aumentare i limiti di upload
COPY php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

# Imposta la directory di lavoro
WORKDIR /var/www/html

# Copia solo i file di Laravel dalla directory src
COPY src/ ./

# Installa le dipendenze di Composer (solo se composer.json esiste)
RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader; fi

# Note: Frontend assets will be built separately due to CKEditor compatibility issues

# Imposta i permessi corretti
RUN chown -R www-data:www-data /var/www/html \
    && if [ -d /var/www/html/storage ]; then chown -R www-data:www-data /var/www/html/storage; fi \
    && if [ -d /var/www/html/bootstrap/cache ]; then chown -R www-data:www-data /var/www/html/bootstrap/cache; fi

# Configura cron per Laravel scheduler
RUN echo "* * * * * cd /var/www/html && php artisan schedule:run >> /var/www/html/storage/logs/scheduler.log 2>&1" | crontab -u www-data -

EXPOSE 9000
CMD service cron start && php-fpm
