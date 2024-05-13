#!/bin/bash
echo "Installing dependencies..."
# Run composer install
docker run --rm -v "$(pwd)"/sequra:/app -w /app composer:latest composer install
# TODO: Run npm install?