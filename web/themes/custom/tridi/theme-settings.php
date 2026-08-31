<?php

declare(strict_types=1);

/**
 * @file
 * Theme settings form for tridicapas theme.
 */

use Drupal\Core\Form\FormState;

/**
 * Implements hook_form_system_theme_settings_alter().
 */
function tridi_form_system_theme_settings_alter(array &$form, FormState $form_state): void {

  $form['tridi'] = [
    '#type' => 'details',
    '#title' => t('tridicapas'),
    '#open' => TRUE,
  ];

  $form['tridi']['example'] = [
    '#type' => 'textfield',
    '#title' => t('Example'),
    '#default_value' => theme_get_setting('example'),
  ];

}
