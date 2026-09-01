#!/bin/bash

set -e

PROJECT_DIR=$HOME"/domains/tridicapas.com"

cd "$PROJECT_DIR"

echo "==> Actualizando código"
git pull origin main

echo "==> Instalando dependencias"
composer install --no-dev --optimize-autoloader

echo "==> Ejecutando Drupal deploy"
vendor/bin/drush deploy

echo "==> Deploy finalizado correctamente"
