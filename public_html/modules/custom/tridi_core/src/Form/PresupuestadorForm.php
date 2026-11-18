<?php

declare(strict_types=1);

namespace Drupal\tridi_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to calculate the budget for a 3D print.
 */
final class PresupuestadorForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'tridi_core_presupuestador';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('tridi_core.presupuesto.settings');

    $form['gramos'] = [
      '#type' => 'number',
      '#title' => $this->t('Gramos'),
      '#description' => $this->t('Peso de filamento utilizado.'),
      '#field_suffix' => 'g',
      '#min' => 0,
      '#step' => 0.01,
      '#required' => TRUE,
    ];

    $form['tiempo'] = [
      '#type' => 'number',
      '#title' => $this->t('Tiempo'),
      '#description' => $this->t('Tiempo de impresión en horas (por ejemplo, 2.5 = 2 h 30 min).'),
      '#field_suffix' => 'h',
      '#min' => 0,
      '#step' => 0.01,
      '#required' => TRUE,
    ];

    $form['costo_filamento'] = [
      '#type' => 'number',
      '#title' => $this->t('Costo filamento por kg'),
      '#field_prefix' => '$',
      '#min' => 0,
      '#step' => 0.01,
      '#required' => TRUE,
    ];

    $form['complejidad'] = [
      '#type' => 'select',
      '#title' => $this->t('Complejidad'),
      '#options' => $this->buildOptions($config->get('complejidad') ?? []),
      '#required' => TRUE,
    ];

    $form['ganancia'] = [
      '#type' => 'select',
      '#title' => $this->t('Ganancia'),
      '#options' => $this->buildOptions($config->get('ganancia') ?? []),
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Calcular el presupuesto'),
      '#button_type' => 'primary',
    ];

    if ($resultado = $form_state->get('resultado')) {
      $form['resultado'] = $this->buildResultado($resultado);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('tridi_core.presupuesto.settings');

    $gramos = (float) $form_state->getValue('gramos');
    $horas = (float) $form_state->getValue('tiempo');
    $costo_kg = (float) $form_state->getValue('costo_filamento');
    $complejidad = $config->get('complejidad')[$form_state->getValue('complejidad')] ?? NULL;
    $ganancia = $config->get('ganancia')[$form_state->getValue('ganancia')] ?? NULL;

    if (!$complejidad || !$ganancia) {
      $this->messenger()->addError($this->t('La configuración del presupuestador está incompleta.'));
      return;
    }

    $costo_filamento = $gramos / 1000 * $costo_kg;
    $costo_tiempo = $horas * (float) $config->get('costo_hora');
    $costo_base = $costo_filamento + $costo_tiempo;
    $subtotal = $costo_base * (float) $complejidad['multiplicador'];
    $monto_ganancia = $subtotal * (float) $ganancia['porcentaje'] / 100;

    $form_state->set('resultado', [
      'costo_filamento' => $costo_filamento,
      'costo_tiempo' => $costo_tiempo,
      'costo_base' => $costo_base,
      'complejidad_label' => $complejidad['label'],
      'complejidad_multiplicador' => (float) $complejidad['multiplicador'],
      'subtotal' => $subtotal,
      'ganancia_label' => $ganancia['label'],
      'ganancia_porcentaje' => (float) $ganancia['porcentaje'],
      'monto_ganancia' => $monto_ganancia,
      'total' => $subtotal + $monto_ganancia,
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
   * Builds the breakdown of the calculated budget.
   */
  private function buildResultado(array $r): array {
    $rows = [
      [$this->t('Costo de filamento'), $this->money($r['costo_filamento'])],
      [$this->t('Costo de tiempo de impresión'), $this->money($r['costo_tiempo'])],
      [$this->t('Costo base'), $this->money($r['costo_base'])],
      [
        $this->t('Complejidad: @label (x@mult)', [
          '@label' => $r['complejidad_label'],
          '@mult' => $r['complejidad_multiplicador'],
        ]),
        $this->money($r['subtotal']),
      ],
      [
        $this->t('Ganancia: @label (@pct%)', [
          '@label' => $r['ganancia_label'],
          '@pct' => $r['ganancia_porcentaje'],
        ]),
        $this->money($r['monto_ganancia']),
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
      'tabla' => [
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
