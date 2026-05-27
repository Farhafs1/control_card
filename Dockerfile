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

# --- THE NATIVE STARTUP FIX ---
# Switch to root to place our script inside the official initialization folder
USER root
COPY deploy.sh /entrypoint.d/99-deploy.sh
RUN chmod +x /entrypoint.d/99-deploy.sh

# Drop back down to application user security context
USER www-data