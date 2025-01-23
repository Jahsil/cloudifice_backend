#!/bin/bash

# Exit immediately if a command exits with a non-zero status
set -e

# Run database migrations
echo "Running database migrations..."

# exec php artisan session:table

# exec php artisan cache:table

# exec php artisan migrate

# Start the Laravel server
echo "Starting Laravel server..."
exec php artisan serve --host=0.0.0.0 --port=8000 
