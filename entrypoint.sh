#!/bin/sh

# Run database migrations automatically
echo "Running migrations..."
php artisan migrate --force

# Start the main server process
echo "Starting web server..."
exec /init