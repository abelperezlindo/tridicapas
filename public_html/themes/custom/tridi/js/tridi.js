/**
 * @file
 * Tridicapas behaviors: menú móvil y zona de arrastre de archivos.
 */
((Drupal, once) => {

  'use strict';

  /**
   * Abre y cierra el menú en pantallas chicas.
   */
  Drupal.behaviors.tridiNavToggle = {
    attach(context) {
      once('tridi-nav-toggle', '.nav-toggle', context).forEach((toggle) => {
        const nav = document.getElementById(toggle.getAttribute('aria-controls'));
        if (!nav) {
          return;
        }

        const setOpen = (open) => {
          nav.classList.toggle('is-open', open);
          toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
          toggle.setAttribute('aria-label', open ? Drupal.t('Cerrar menú') : Drupal.t('Abrir menú'));
        };

        toggle.addEventListener('click', () => {
          setOpen(!nav.classList.contains('is-open'));
        });

        // Al elegir un enlace (p. ej. un ancla de la portada) se cierra el menú.
        nav.addEventListener('click', (event) => {
          if (event.target.closest('a')) {
            setOpen(false);
          }
        });

        document.addEventListener('keydown', (event) => {
          if (event.key === 'Escape' && nav.classList.contains('is-open')) {
            setOpen(false);
            toggle.focus();
          }
        });
      });
    },
  };

  /**
   * Muestra el nombre del archivo elegido y acepta arrastrar y soltar.
   *
   * Uso: <label class="dropzone" for="ID" data-tridi-dropzone> + <input
   * type="file" id="ID">. El texto se muestra en .dropzone__text.
   */
  Drupal.behaviors.tridiDropzone = {
    attach(context) {
      once('tridi-dropzone', '[data-tridi-dropzone]', context).forEach((zone) => {
        const input = document.getElementById(zone.getAttribute('for'));
        const text = zone.querySelector('.dropzone__text');
        if (!input || !text) {
          return;
        }
        const defaultText = text.textContent;

        input.addEventListener('change', () => {
          text.textContent = input.files.length ? input.files[0].name : defaultText;
        });

        ['dragover', 'dragenter'].forEach((type) => {
          zone.addEventListener(type, (event) => {
            event.preventDefault();
            zone.classList.add('is-drag');
          });
        });

        ['dragleave', 'drop'].forEach((type) => {
          zone.addEventListener(type, () => zone.classList.remove('is-drag'));
        });

        zone.addEventListener('drop', (event) => {
          event.preventDefault();
          if (event.dataTransfer.files.length) {
            input.files = event.dataTransfer.files;
            input.dispatchEvent(new Event('change'));
          }
        });
      });
    },
  };

})(Drupal, once);
