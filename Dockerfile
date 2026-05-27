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
# Switch to root to copy files into system bin and adjust execution flags
USER root
COPY deploy.sh /usr/local/bin/deploy.sh
RUN chmod +x /usr/local/bin/deploy.sh

# Drop back down to application user security before running the app
USER www-data

# Set the execution target
ENTRYPOINT ["/usr/local/bin/deploy.sh"]