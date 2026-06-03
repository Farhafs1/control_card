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

# --- THE NGINX PATH OVERRIDE FIX ---
ENV NGINX_CONF_FILE=/etc/nginx/nginx.conf

# Copy your application code over
COPY --chown=www-data:www-data . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Expose standard web traffic port
EXPOSE 8080

# --- FIX: INSTALL LATEST NODE.JS & COMPILE VITE ASSETS ---
USER root

RUN apt-get update && apt-get install -y curl \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Switch back to non-root application user
USER www-data

# Clean install node assets and execute production compilation matrix
RUN npm ci && npm run build

# --- ADD THESE LINES AT THE VERY BOTTOM ---
USER root
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
USER www-data

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]