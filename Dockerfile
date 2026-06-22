FROM php:8.1-apache

# Gerekli PHP eklentilerini kur
RUN docker-php-ext-install pdo pdo_mysql

# Çalışma dizinini ayarla
COPY . /var/www/html/

# Apache ayarları
RUN chown -R www-data:www-data /var/www/html \
    && a2enmod rewrite

# Portu ayarla
EXPOSE 80
