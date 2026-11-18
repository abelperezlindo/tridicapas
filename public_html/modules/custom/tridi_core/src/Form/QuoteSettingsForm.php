<?php

declare(strict_types=1);

namespace Drupal\tridi_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings for the values used by the quote calculator.
 */
final class QuoteSettingsForm extends ConfigFormBase {

  private const CONFIG_NAME = 'tridi_core.quote.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'tridi_core_quote_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(self::CONFIG_NAME);

    $form['hourly_cost'] = [
      '#type' => 'number',
      '#title' => $this->t('Costo por hora de impresión'),
      '#description' => $this->t('Electricidad, desgaste y amortización de la impresora por cada hora.'),
      '#field_prefix' => '$',
      '#min' => 0,
      '#step' => 0.01,
      '#default_value' => $config->get('hourly_cost'),
      '#required' => TRUE,
    ];

    $form['complexity'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Complejidad (multiplicador)'),
      '#description' => $this->t('El costo base se multiplica por este valor. 1 = sin recargo.'),
      '#tree' => TRUE,
    ];
    foreach ($config->get('complexity') ?? [] as $key => $item) {
      $form['complexity'][$key] = [
        '#type' => 'number',
        '#title' => $item['label'],
        '#field_prefix' => 'x',
        '#min' => 0,
        '#step' => 0.01,
        '#default_value' => $item['multiplier'],
        '#required' => TRUE,
      ];
    }

    $form['profit'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Ganancia (porcentaje)'),
      '#description' => $this->t('Porcentaje que se suma sobre el costo con complejidad aplicada.'),
      '#tree' => TRUE,
    ];
    foreach ($config->get('profit') ?? [] as $key => $item) {
      $form['profit'][$key] = [
        '#type' => 'number',
        '#title' => $item['label'],
        '#field_suffix' => '%',
        '#min' => 0,
        '#step' => 0.01,
        '#default_value' => $item['percentage'],
        '#required' => TRUE,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config(self::CONFIG_NAME);

    $complexity = $config->get('complexity') ?? [];
    foreach ($form_state->getValue('complexity') as $key => $value) {
      $complexity[$key]['multiplier'] = (float) $value;
    }

    $profit = $config->get('profit') ?? [];
    foreach ($form_state->getValue('profit') as $key => $value) {
      $profit[$key]['percentage'] = (float) $value;
    }

    $config
      ->set('hourly_cost', (float) $form_state->getValue('hourly_cost'))
      ->set('complexity', $complexity)
      ->set('profit', $profit)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
