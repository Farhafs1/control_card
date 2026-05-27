FROM serversideup/php:8.4-fpm-nginx

# Set the working directory inside the server
WORKDIR /var/www/html

# Switch to root user temporarily to install system extensions
USER root
RUN install-php-extensions gd
USER www-data

# Prevent Laravel from trying to boot a live DB connection during build time
ENV DB_CONNECTION=null
ENV ALL_COMMANDS_IGNORE_DB=true

# Copy your application code over
COPY --chown=www-data:www-data . .

# Install dependencies while bypassing live script execution hooks during build
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Expose standard web traffic port
EXPOSE 8080

# --- THE FIX FOR AUTOMATIC MIGRATIONS AND SYSTEM RESETS ---
# This executes your migrations at runtime, then launches the native webserver engine cleanly.
CMD php artisan migrate --force && exec /init