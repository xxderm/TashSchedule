FROM php:8.2-apache

COPY ./www /var/www/html

RUN docker-php-ext-install pdo pdo_mysql 

ENV MYSQL_ROOT_PASSWORD 123
ENV MYSQL_DATABASE myDb
COPY ./www/myDb.sql /docker-entrypoint-initdb.d/

EXPOSE 80

CMD ["apache2-foreground"]