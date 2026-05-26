#!/bin/sh

# Trigger the base image's built-in webserver configuration tools first
echo "Initializing webserver configs..."
/usr/local/bin/entrypoint-webserver

# Run database migrations automatically now that the DB name is fixed
echo "Running migrations..."
php artisan migrate --force

# Start the actual Nnginx and PHP-FPM processes smoothly
echo "Starting web server..."
exec /init