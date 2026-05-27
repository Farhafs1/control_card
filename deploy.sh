#!/bin/bash

# 1. Run the database migrations safely at boot
echo "🚀 Running database migrations..."
php artisan migrate --force


# # 2. Hand control back over to the image's original entrypoint 
# echo "🎬 Starting Nginx and PHP-FPM..."
# exec /entrypoint /init