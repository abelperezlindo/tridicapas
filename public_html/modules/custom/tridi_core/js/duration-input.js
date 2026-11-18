/**
 * @file
 * Input mask for duration fields in the "3h 20m" format.
 */
((Drupal, once) => {

  'use strict';

  /**
   * Parses a duration string into hours and minutes.
   *
   * Mirrors QuoteCalculatorForm::parseDuration() on the server.
   */
  const parse = (value) => {
    const match = value.trim().match(/^(?:(\d+)\s*h)?\s*(?:(\d+)\s*m)?$/i);
    if (!match || (match[1] === undefined && match[2] === undefined)) {
      return null;
    }
    return {
      hours: match[1] === undefined ? null : parseInt(match[1], 10),
      minutes: match[2] === undefined ? null : parseInt(match[2], 10),
    };
  };

  Drupal.behaviors.tridiDurationInput = {
    attach(context) {
      once('tridi-duration-input', 'input[data-duration-input]', context).forEach((input) => {
        input.addEventListener('input', () => {
          // Only digits, spaces and the "h" / "m" unit letters are allowed.
          const clean = input.value.replace(/[^0-9hm ]/gi, '').toLowerCase();
          if (clean !== input.value) {
            const pos = Math.max(0, input.selectionStart - (input.value.length - clean.length));
            input.value = clean;
            input.setSelectionRange(pos, pos);
          }
          input.setCustomValidity('');
        });

        input.addEventListener('blur', () => {
          if (input.value.trim() === '') {
            return;
          }
          const duration = parse(input.value);
          if (!duration) {
            input.setCustomValidity(Drupal.t('Usá el formato horas y minutos, por ejemplo 3h 20m, 20h o 50m.'));
            input.reportValidity();
            return;
          }
          // Normalize the spacing, e.g. "3H20M" becomes "3h 20m".
          const parts = [];
          if (duration.hours !== null) {
            parts.push(`${duration.hours}h`);
          }
          if (duration.minutes !== null) {
            parts.push(`${duration.minutes}m`);
          }
          input.value = parts.join(' ');
        });
      });
    },
  };

})(Drupal, once);
