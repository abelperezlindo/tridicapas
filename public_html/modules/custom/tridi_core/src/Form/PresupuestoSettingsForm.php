<?php

declare(strict_types=1);

namespace Drupal\tridi_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings for the values used by the budget calculator.
 */
final class PresupuestoSettingsForm extends ConfigFormBase {

  private const CONFIG_NAME = 'tridi_core.presupuesto.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'tridi_core_presupuesto_settings';
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

    $form['costo_hora'] = [
      '#type' => 'number',
      '#title' => $this->t('Costo por hora de impresión'),
      '#description' => $this->t('Electricidad, desgaste y amortización de la impresora por cada hora.'),
      '#field_prefix' => '$',
      '#min' => 0,
      '#step' => 0.01,
      '#default_value' => $config->get('costo_hora'),
      '#required' => TRUE,
    ];

    $form['complejidad'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Complejidad (multiplicador)'),
      '#description' => $this->t('El costo base se multiplica por este valor. 1 = sin recargo.'),
      '#tree' => TRUE,
    ];
    foreach ($config->get('complejidad') ?? [] as $key => $item) {
      $form['complejidad'][$key] = [
        '#type' => 'number',
        '#title' => $item['label'],
        '#field_prefix' => 'x',
        '#min' => 0,
        '#step' => 0.01,
        '#default_value' => $item['multiplicador'],
        '#required' => TRUE,
      ];
    }

    $form['ganancia'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Ganancia (porcentaje)'),
      '#description' => $this->t('Porcentaje que se suma sobre el costo con complejidad aplicada.'),
      '#tree' => TRUE,
    ];
    foreach ($config->get('ganancia') ?? [] as $key => $item) {
      $form['ganancia'][$key] = [
        '#type' => 'number',
        '#title' => $item['label'],
        '#field_suffix' => '%',
        '#min' => 0,
        '#step' => 0.01,
        '#default_value' => $item['porcentaje'],
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

    $complejidad = $config->get('complejidad') ?? [];
    foreach ($form_state->getValue('complejidad') as $key => $value) {
      $complejidad[$key]['multiplicador'] = (float) $value;
    }

    $ganancia = $config->get('ganancia') ?? [];
    foreach ($form_state->getValue('ganancia') as $key => $value) {
      $ganancia[$key]['porcentaje'] = (float) $value;
    }

    $config
      ->set('costo_hora', (float) $form_state->getValue('costo_hora'))
      ->set('complejidad', $complejidad)
      ->set('ganancia', $ganancia)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
