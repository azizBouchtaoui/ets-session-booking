#!/bin/sh
set -e

if [ ! -d "vendor" ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if [ ! -f "config/jwt/private.pem" ]; then
    mkdir -p config/jwt
    openssl genpkey -algorithm RSA \
        -out config/jwt/private.pem \
        -aes256 -pass env:JWT_PASSPHRASE
    openssl pkey \
        -in config/jwt/private.pem \
        -out config/jwt/public.pem \
        -pubout -passin env:JWT_PASSPHRASE
    chmod 644 config/jwt/private.pem config/jwt/public.pem
    echo "JWT keys generated."
fi

exec "$@"
