FROM php:8.2-apache

WORKDIR /var/www/html

RUN docker-php-ext-install mysqli pdo pdo_mysql \
    && a2enmod rewrite headers

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
