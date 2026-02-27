# Stage 1: Composer dependencies (no dev)
FROM composer:2 AS composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader
COPY . .
RUN composer dump-autoload --optimize --no-dev

# Stage 2: Frontend build
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 3: Prepare app
FROM alpine:3.20 AS app
WORKDIR /app
COPY --from=composer /app .
COPY --from=frontend /app/public/build public/build
# Remove dev files
RUN rm -rf tests node_modules .env.example .git .github storage/logs/*.log \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs storage/app bootstrap/cache

# Stage 4: Build FrankenPHP v1.11.3 static binary (PHP 8.4)
FROM dunglas/frankenphp:static-builder-musl-1.11.3 AS builder
WORKDIR /go/src/app

# Copy app into a separate embed directory (must NOT be under dist/ which
# gets polluted by static-php-cli build artifacts — causing app.tar > 2GB)
COPY --from=app /app /go/src/app/embed/app
COPY Caddyfile /go/src/app/embed/Caddyfile

# Build with PHP 8.4 and required extensions
RUN EMBED=/go/src/app/embed \
    PHP_VERSION=8.4 \
    PHP_EXTENSIONS="ctype,curl,dom,fileinfo,filter,hash,iconv,json,mbstring,openssl,pcre,pdo,pdo_sqlite,phar,posix,readline,session,sockets,sqlite3,tokenizer,xml,xmlwriter,zlib,bcmath,intl,sodium" \
    PHP_EXTENSION_LIBS="bzip2,freetype,libavif,libjpeg,liblz4,libwebp,libzip,nghttp2,zstd,libssh2,ngtcp2,nghttp3,ldap" \
    ./build-static.sh
