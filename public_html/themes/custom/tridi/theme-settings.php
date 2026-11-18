<?php

declare(strict_types=1);

/**
 * @file
 * Theme settings form for tridicapas theme.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_system_theme_settings_alter().
 */
function tridi_form_system_theme_settings_alter(array &$form, FormStateInterface $form_state): void {

  $form['tridi'] = [
    '#type' => 'details',
    '#title' => t('Tridicapas'),
    '#open' => TRUE,
  ];

  $form['tridi']['instagram_user'] = [
    '#type' => 'textfield',
    '#title' => t('Usuario de Instagram'),
    '#description' => t('Sin "@". Se usa en el botón "Escribinos" del header y en la sección de Instagram de la portada. Dejar vacío para ocultarlos.'),
    '#default_value' => theme_get_setting('instagram_user') ?? 'tridicapas',
    '#field_prefix' => '@',
  ];

}
