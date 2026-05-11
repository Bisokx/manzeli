FROM php:8.2-cli

RUN docker-php-ext-install pdo pdo_mysql mysqli

RUN apt-get update && apt-get install -y libcurl4-openssl-dev \
    && docker-php-ext-install curl \
    && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html/

WORKDIR /var/www/html

EXPOSE 80

CMD ["php", "-S", "0.0.0.0:80"]
