FROM richarvey/nginx-php-fpm:latest-php8.4

# Set the working directory inside the server
COPY . /var/www/html

# Tell the server to target Laravel's public directory
ENV WEBROOT /var/www/html/public
ENV APP_ENV production

# Install dependencies smoothly
RUN composer install --no-dev --optimize-autoloader

# Set correct server permissions automatically
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache