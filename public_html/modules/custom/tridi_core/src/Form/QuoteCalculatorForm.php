<?php

declare(strict_types=1);

namespace Drupal\tridi_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to calculate the quote for a 3D print.
 */
final class QuoteCalculatorForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'tridi_core_quote_calculator';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('tridi_core.quote.settings');

    $form['grams'] = [
      '#type' => 'number',
      '#title' => $this->t('Gramos'),
      '#description' => $this->t('Peso de filamento utilizado.'),
      '#field_suffix' => 'g',
      '#min' => 0,
      '#step' => 0.01,
      '#required' => TRUE,
    ];

    $form['hours'] = [
      '#type' => 'number',
      '#title' => $this->t('Tiempo'),
      '#description' => $this->t('Tiempo de impresión en horas (por ejemplo, 2.5 = 2 h 30 min).'),
      '#field_suffix' => 'h',
      '#min' => 0,
      '#step' => 0.01,
      '#required' => TRUE,
    ];

    $form['filament_cost_per_kg'] = [
      '#type' => 'number',
      '#title' => $this->t('Costo filamento por kg'),
      '#field_prefix' => '$',
      '#min' => 0,
      '#step' => 0.01,
      '#required' => TRUE,
    ];

    $form['complexity'] = [
      '#type' => 'select',
      '#title' => $this->t('Complejidad'),
      '#options' => $this->buildOptions($config->get('complexity') ?? []),
      '#required' => TRUE,
    ];

    $form['profit'] = [
      '#type' => 'select',
      '#title' => $this->t('Ganancia'),
      '#options' => $this->buildOptions($config->get('profit') ?? []),
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Calcular el presupuesto'),
      '#button_type' => 'primary',
    ];

    if ($result = $form_state->get('result')) {
      $form['result'] = $this->buildResult($result);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('tridi_core.quote.settings');

    $grams = (float) $form_state->getValue('grams');
    $hours = (float) $form_state->getValue('hours');
    $cost_per_kg = (float) $form_state->getValue('filament_cost_per_kg');
    $complexity = $config->get('complexity')[$form_state->getValue('complexity')] ?? NULL;
    $profit = $config->get('profit')[$form_state->getValue('profit')] ?? NULL;

    if (!$complexity || !$profit) {
      $this->messenger()->addError($this->t('La configuración del presupuestador está incompleta.'));
      return;
    }

    $filament_cost = $grams / 1000 * $cost_per_kg;
    $time_cost = $hours * (float) $config->get('hourly_cost');
    $base_cost = $filament_cost + $time_cost;
    $subtotal = $base_cost * (float) $complexity['multiplier'];
    $profit_amount = $subtotal * (float) $profit['percentage'] / 100;

    $form_state->set('result', [
      'filament_cost' => $filament_cost,
      'time_cost' => $time_cost,
      'base_cost' => $base_cost,
      'complexity_label' => $complexity['label'],
      'complexity_multiplier' => (float) $complexity['multiplier'],
      'subtotal' => $subtotal,
      'profit_label' => $profit['label'],
      'profit_percentage' => (float) $profit['percentage'],
      'profit_amount' => $profit_amount,
      'total' => $subtotal + $profit_amount,
    ]);
    $form_state->setRebuild();
  }

  /**
   * Converts the configured values into select options.
   */
  private function buildOptions(array $items): array {
    return array_map(static fn(array $item): string => (string) $item['label'], $items);
  }

  /**
   * Builds the breakdown of the calculated quote.
   */
  private function buildResult(array $r): array {
    $rows = [
      [$this->t('Costo de filamento'), $this->money($r['filament_cost'])],
      [$this->t('Costo de tiempo de impresión'), $this->money($r['time_cost'])],
      [$this->t('Costo base'), $this->money($r['base_cost'])],
      [
        $this->t('Complejidad: @label (x@mult)', [
          '@label' => $r['complexity_label'],
          '@mult' => $r['complexity_multiplier'],
        ]),
        $this->money($r['subtotal']),
      ],
      [
        $this->t('Ganancia: @label (@pct%)', [
          '@label' => $r['profit_label'],
          '@pct' => $r['profit_percentage'],
        ]),
        $this->money($r['profit_amount']),
      ],
      [
        ['data' => ['#markup' => '<strong>' . $this->t('Total') . '</strong>']],
        ['data' => ['#markup' => '<strong>' . $this->money($r['total']) . '</strong>']],
      ],
    ];

    return [
      '#type' => 'details',
      '#title' => $this->t('Presupuesto'),
      '#open' => TRUE,
      '#weight' => 100,
      'table' => [
        '#type' => 'table',
        '#header' => [$this->t('Concepto'), $this->t('Importe')],
        '#rows' => $rows,
      ],
    ];
  }

  /**
   * Formats a monetary amount.
   */
  private function money(float $amount): string {
    return '$ ' . number_format($amount, 2, ',', '.');
  }

}
