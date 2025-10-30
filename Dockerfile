FROM php:latest AS base
FROM composer:latest AS composer
FROM node:latest
COPY --from=base /usr /usr
COPY --from=base /etc /etc
COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN apt-get update
RUN apt-get -o Dpkg::Options::="--force-confnew" upgrade -y
RUN apt-get install -y iputils-ping git mc
RUN composer self-update
RUN npm i -g purgecss
RUN npm install -g pnpm
WORKDIR /src
ENTRYPOINT ["/bin/bash"]
