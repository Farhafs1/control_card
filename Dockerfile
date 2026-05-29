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
# Force Render/Nginx to look at the native base templates
ENV NGINX_CONF_FILE=/etc/nginx/nginx.conf

# Copy your application code over
COPY --chown=www-data:www-data . .

# Install dependencies while bypassing live script execution hooks during build
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Expose standard web traffic port
EXPOSE 8080

# --- FIX: INSTALL LATEST NODE.JS & COMPILE VITE ASSETS ---
USER root

# Install curl, clear out old instances, and download NodeSorce 20.x LTS (Recommended for Vite)
RUN apt-get update && apt-get install -y curl \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Switch back to non-root application user for file permission mapping
USER www-data

# Clean install node assets and execute production compilation matrix
RUN npm ci && npm run build