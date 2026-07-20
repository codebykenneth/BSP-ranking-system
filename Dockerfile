FROM php:8.2-apache

# Install PostgreSQL support for PHP
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Copy your app files into the web server folder
COPY . /var/www/html/

# Allow Apache to write uploaded photos to the uploads folder
RUN chown -R www-data:www-data /var/www/html/assets/uploads

# Render expects apps to listen on port 10000
RUN sed -i 's/80/10000/g' /etc/apache2/ports.conf /etc/apache2/sites-enabled/000-default.conf

EXPOSE 10000
