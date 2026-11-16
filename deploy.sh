#!/bin/bash

set -e

PROJECT_DIR=$HOME"/domains/tridicapas.com"

cd "$PROJECT_DIR"

# Log ussing drupal logger: Pulling latest changes from git
vendor/bin/drush php:eval "\Drupal::logger('Deploy.sh')->info('Pulling latest');"
git pull origin main

# Log ussing drupal logger: Installing composer dependencies
vendor/bin/drush php:eval "\Drupal::logger('Deploy.sh')->info('Installing composer dependencies');"
composer install --no-dev --optimize-autoloader

# Log ussing drupal logger: Running Drupal deploy
vendor/bin/drush php:eval "\Drupal::logger('Deploy.sh')->info('Running Drupal deploy');"
vendor/bin/drush deploy

# Log ussing drupal logger: End
vendor/bin/drush php:eval "\Drupal::logger('Deploy.sh')->info('Deploy finalizado correctamente');"

