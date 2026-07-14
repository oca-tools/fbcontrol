/* Fila de check-in temático: busca instantânea + filtro de status client-side.
   Enhancement progressivo — sem JS, a fila completa continua visível e acionável.
   Nota: .fb-bilhete e .fb-turno-group são display:grid, então o atributo [hidden]
   não os esconde (a regra CSS vence) — usamos style.display. */
(function () {
    'use strict';

    var DIACRITICOS = new RegExp('[\\u0300-\\u036f]', 'g');

    function normalizar(texto) {
        return (texto || '')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(DIACRITICOS, '');
    }

    function init() {
        var busca = document.querySelector('[data-fb-checkin-search]');
        var chipsWrap = document.querySelector('[data-fb-checkin-chips]');
        if (!busca && !chipsWrap) {
            return;
        }
        var bilhetes = Array.prototype.slice.call(document.querySelectorAll('.fb-bilhete'));
        if (!bilhetes.length) {
            return;
        }
        var grupos = Array.prototype.slice.call(document.querySelectorAll('.fb-turno-group'));
        var contador = document.querySelector('[data-fb-checkin-count]');
        var semResultado = document.querySelector('[data-fb-checkin-noresults]');
        var termo = '';
        var estado = '';

        function aplicar() {
            var visiveis = 0;
            bilhetes.forEach(function (bilhete) {
                var okBusca = !termo || normalizar(bilhete.getAttribute('data-busca')).indexOf(termo) >= 0;
                var okEstado = !estado || bilhete.getAttribute('data-estado') === estado;
                var visivel = okBusca && okEstado;
                bilhete.style.display = visivel ? '' : 'none';
                if (visivel) {
                    visiveis++;
                }
            });
            grupos.forEach(function (grupo) {
                var algum = grupo.querySelector('.fb-bilhete:not([style*="display: none"])');
                grupo.style.display = algum ? '' : 'none';
            });
            if (semResultado) {
                semResultado.hidden = visiveis !== 0;
            }
            if (contador) {
                var filtrando = termo !== '' || estado !== '';
                contador.textContent = filtrando ? (visiveis + (visiveis === 1 ? ' resultado' : ' resultados')) : '';
            }
        }

        if (busca) {
            termo = normalizar(busca.value.trim());
            busca.addEventListener('input', function () {
                termo = normalizar(busca.value.trim());
                aplicar();
            });
        }

        if (chipsWrap) {
            chipsWrap.addEventListener('click', function (e) {
                var chip = e.target.closest ? e.target.closest('[data-status-filter]') : null;
                if (!chip) {
                    return;
                }
                estado = chip.getAttribute('data-status-filter') || '';
                var todos = chipsWrap.querySelectorAll('.fb-chip');
                for (var i = 0; i < todos.length; i++) {
                    todos[i].classList.toggle('fb-chip--active', todos[i] === chip);
                }
                aplicar();
            });
        }

        aplicar();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
