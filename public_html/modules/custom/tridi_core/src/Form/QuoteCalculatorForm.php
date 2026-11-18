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
   * HTML pattern for the duration field (browsers anchor it implicitly).
   */
  private const DURATION_PATTERN = '\\s*(\\d+\\s*[hH])?\\s*(\\d+\\s*[mM])?\\s*';

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

    $form['#attributes']['class'][] = 'form-card';
    $form['#attached']['library'][] = 'tridi_core/duration_input';

    $form['grams'] = [
      '#type' => 'number',
      '#title' => $this->t('Gramos'),
      '#description' => $this->t('Peso de filamento utilizado.'),
      '#placeholder' => '120',
      '#field_suffix' => 'g',
      '#min' => 0,
      '#step' => 0.01,
      '#required' => TRUE,
    ];

    $form['duration'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tiempo'),
      '#description' => $this->t('Tiempo de impresión en horas y minutos, por ejemplo 3h 20m, 20h o 50m.'),
      '#placeholder' => '3h 20m',
      '#size' => 12,
      '#maxlength' => 16,
      '#attributes' => [
        // Client-side hint only; the authoritative check is validateForm().
        'pattern' => self::DURATION_PATTERN,
        'data-duration-input' => '',
        'autocomplete' => 'off',
        'title' => $this->t('Horas y minutos, por ejemplo 3h 20m'),
      ],
      '#required' => TRUE,
    ];

    $form['filament_cost_per_kg'] = [
      '#type' => 'number',
      '#title' => $this->t('Costo filamento por kg'),
      '#placeholder' => '25000',
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
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Core already checks required values, numeric format, #min and that the
    // selected options exist; these are the business rules on top of that.
    $grams = $form_state->getValue('grams');
    if (is_numeric($grams) && (float) $grams <= 0) {
      $form_state->setErrorByName('grams', $this->t('Los gramos deben ser mayores que cero.'));
    }

    $cost_per_kg = $form_state->getValue('filament_cost_per_kg');
    if (is_numeric($cost_per_kg) && (float) $cost_per_kg <= 0) {
      $form_state->setErrorByName('filament_cost_per_kg', $this->t('El costo del filamento debe ser mayor que cero.'));
    }

    $duration = trim((string) $form_state->getValue('duration'));
    if ($duration !== '') {
      $minutes = self::parseDuration($duration);
      if ($minutes === NULL) {
        $form_state->setErrorByName('duration', $this->t('El tiempo debe tener el formato horas y minutos, por ejemplo 3h 20m, 20h o 50m. Si indicás horas, los minutos van de 0 a 59.'));
      }
      elseif ($minutes === 0) {
        $form_state->setErrorByName('duration', $this->t('El tiempo debe ser mayor que cero.'));
      }
      else {
        $form_state->setValue('duration_minutes', $minutes);
      }
    }

    $config = $this->config('tridi_core.quote.settings');
    foreach (['complexity', 'profit'] as $key) {
      if (!isset($config->get($key)[$form_state->getValue($key)])) {
        $form_state->setErrorByName($key, $this->t('La configuración del presupuestador está incompleta.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('tridi_core.quote.settings');

    $grams = (float) $form_state->getValue('grams');
    $minutes = (int) $form_state->getValue('duration_minutes');
    $cost_per_kg = (float) $form_state->getValue('filament_cost_per_kg');
    $complexity = $config->get('complexity')[$form_state->getValue('complexity')];
    $profit = $config->get('profit')[$form_state->getValue('profit')];

    $filament_cost = $grams / 1000 * $cost_per_kg;
    $time_cost = $minutes / 60 * (float) $config->get('hourly_cost');
    $base_cost = $filament_cost + $time_cost;
    $subtotal = $base_cost * (float) $complexity['multiplier'];
    $profit_amount = $subtotal * (float) $profit['percentage'] / 100;

    $form_state->set('result', [
      'filament_cost' => $filament_cost,
      'minutes' => $minutes,
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
   * Parses a duration such as "3h 20m", "20h" or "50m" into minutes.
   *
   * Units are case-insensitive and spaces are optional. When hours are given,
   * minutes must be between 0 and 59.
   *
   * @return int|null
   *   The total minutes, or NULL if the format is invalid.
   */
  public static function parseDuration(string $value): ?int {
    if (!preg_match('/^\s*(?:(\d{1,4})\s*h)?\s*(?:(\d{1,5})\s*m)?\s*$/i', $value, $matches)) {
      return NULL;
    }
    $hours = ($matches[1] ?? '') === '' ? NULL : (int) $matches[1];
    $minutes = ($matches[2] ?? '') === '' ? NULL : (int) $matches[2];
    if ($hours === NULL && $minutes === NULL) {
      return NULL;
    }
    if ($hours !== NULL && $minutes !== NULL && $minutes > 59) {
      return NULL;
    }
    return ($hours ?? 0) * 60 + ($minutes ?? 0);
  }

  /**
   * Formats minutes as "3 h 20 min".
   */
  private static function formatDuration(int $minutes): string {
    $parts = [];
    if ($hours = intdiv($minutes, 60)) {
      $parts[] = $hours . ' h';
    }
    if ($rest = $minutes % 60) {
      $parts[] = $rest . ' min';
    }
    return implode(' ', $parts);
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
      [
        $this->t('Costo de tiempo de impresión (@duration)', [
          '@duration' => self::formatDuration($r['minutes']),
        ]),
        $this->money($r['time_cost']),
      ],
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
