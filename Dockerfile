FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN a2enmod headers expires deflate

RUN {
	echo 'opcache.enable=1';
	echo 'opcache.enable_cli=0';
	echo 'opcache.memory_consumption=192';
	echo 'opcache.interned_strings_buffer=16';
	echo 'opcache.max_accelerated_files=20000';
	echo 'opcache.validate_timestamps=1';
	echo 'opcache.revalidate_freq=2';
} > /usr/local/etc/php/conf.d/opcache-recommended.ini

COPY . /var/www/html/

EXPOSE 80