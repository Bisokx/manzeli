FROM php:8.2-cli

RUN docker-php-ext-install pdo pdo_mysql

RUN apt-get update && apt-get install -y libcurl4-openssl-dev \
    && docker-php-ext-install curl \
    && rm -rf /var/lib/apt/lists/*

COPY . /app/

WORKDIR /app

EXPOSE 80

CMD ["php", "-S", "0.0.0.0:80", "-t", "/app"]
