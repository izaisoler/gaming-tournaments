FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql

ENV APACHE_LOG_DIR /var/log/apache2

COPY . /var/www/html/

RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

EXPOSE ${PORT}

CMD ["apache2-foreground"]
