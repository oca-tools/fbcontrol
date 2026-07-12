/* Launcher de módulos: filtro de azulejos por texto (enhancement progressivo).
   Sem dependências; se o JS não carregar, a grade continua navegável. */
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

    function filtrar(input) {
        var raiz = input.closest('.fb-launcher') || document;
        var termo = normalizar(input.value.trim());
        var tiles = raiz.querySelectorAll('.fb-tile');
        for (var i = 0; i < tiles.length; i++) {
            var alvo = normalizar(tiles[i].getAttribute('data-search') || tiles[i].textContent);
            tiles[i].style.display = (!termo || alvo.indexOf(termo) >= 0) ? '' : 'none';
        }
        var secoes = raiz.querySelectorAll('.fb-launcher__section');
        for (var j = 0; j < secoes.length; j++) {
            var visivel = secoes[j].querySelector('.fb-tile:not([style*="display: none"])');
            secoes[j].style.display = visivel ? '' : 'none';
        }
    }

    document.addEventListener('input', function (e) {
        var input = e.target.closest ? e.target.closest('[data-fb-launcher-search]') : null;
        if (input) {
            filtrar(input);
        }
    });

    /* Ao fechar o launcher, limpa a busca para reabrir limpo. */
    document.addEventListener('hidden.bs.offcanvas', function (e) {
        var input = e.target.querySelector ? e.target.querySelector('[data-fb-launcher-search]') : null;
        if (input) {
            input.value = '';
            filtrar(input);
        }
    });
})();
