FROM serversideup/php:8.4-fpm-nginx

# Set the working directory inside the server
WORKDIR /var/www/html

# Switch to root user temporarily to install system extensions
USER root
RUN install-php-extensions gd
USER www-data

# Prevent Laravel from trying to boot a live DB connection during build
ENV DB_CONNECTION=null
ENV ALL_COMMANDS_IGNORE_DB=true

# Copy your application code over
COPY --chown=www-data:www-data . .

# Install dependencies while bypassing live script execution hooks
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Switch back to root to configure execution permissions on our startup script
USER root
RUN chmod +x /var/www/html/entrypoint.sh
USER www-data

# Expose standard web traffic port
EXPOSE 8080

# Override the default startup to use our entrypoint script
ENTRYPOINT ["/var/www/html/entrypoint.sh"]