FROM ubuntu:22.04

RUN apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y \
    libreoffice \
    php \
    php-cli \
    php-curl \
    curl \
    unzip \
    && apt-get clean

WORKDIR /var/www/html
COPY . .

RUN mkdir -p storage/uploads storage/converted
EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000"]
