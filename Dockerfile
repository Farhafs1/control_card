FROM serversideup/php:8.4-fpm-nginx

# Set the working directory inside the server
WORKDIR /var/www/html

# Copy your application code over
COPY --chown=www-data:www-data . .

# Run composer installation smoothly for production
RUN composer install --no-dev --optimize-autoloader

# Expose standard web traffic port
EXPOSE 8080