/*
 * fb-filters.js — filtros em chips + folha inferior (revisão de marca Oca).
 * Enhancement progressivo: transforma cada <form data-fb-filters> numa barra de
 * chips resumindo os filtros ativos + botão "Filtros" que abre o formulário como
 * folha inferior (mobile) ou painel inline (desktop). Sem JS, o formulário aparece
 * normalmente. Nenhuma mudança de backend: o form continua submetendo por GET.
 */
(function () {
    'use strict';

    var MESES = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

    function formatarData(valor) {
        var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(valor);
        if (!m) { return valor; }
        return parseInt(m[3], 10) + ' ' + MESES[parseInt(m[2], 10) - 1];
    }

    function rotuloCampo(el) {
        var valor = (el.value || '').trim();
        if (!valor) { return null; }
        if (el.tagName === 'SELECT') {
            if (el.selectedIndex <= 0) { return null; }  // 1ª opção = "Todos/Todas"
            return el.options[el.selectedIndex].text.trim();
        }
        if (el.type === 'date') { return formatarData(valor); }
        if (el.type === 'hidden' || el.type === 'submit' || el.type === 'button') { return null; }
        return valor;
    }

    function camposFiltraveis(form) {
        return Array.prototype.filter.call(
            form.querySelectorAll('input, select'),
            function (el) {
                return el.type !== 'hidden' && el.type !== 'submit' && el.type !== 'button';
            }
        );
    }

    function resumir(form) {
        var chips = [];
        camposFiltraveis(form).forEach(function (el) {
            var r = rotuloCampo(el);
            if (r) { chips.push(r); }
        });
        return chips;
    }

    function montar(form) {
        var host = form.parentElement;
        if (!host) { return; }

        // Barra de chips (visível no mobile; escondida no desktop, onde o form fica inline).
        var bar = document.createElement('div');
        bar.className = 'fb-filterbar';

        var chipsWrap = document.createElement('div');
        chipsWrap.className = 'fb-filterbar__chips';
        bar.appendChild(chipsWrap);

        var botao = document.createElement('button');
        botao.type = 'button';
        botao.className = 'fb-filterbar__toggle';
        botao.innerHTML = '<i class="bi bi-sliders"></i> <span>Filtros</span>';
        bar.appendChild(botao);

        host.insertBefore(bar, form);

        // Folha: o próprio form vira .fb-sheet; alça + backdrop para o modo mobile.
        var backdrop = document.createElement('div');
        backdrop.className = 'fb-sheet-backdrop';
        host.insertBefore(backdrop, form);

        form.classList.add('fb-sheet', 'fb-filters-sheet');
        var alca = document.createElement('div');
        alca.className = 'fb-sheet__handle';
        form.insertBefore(alca, form.firstChild);

        function atualizarChips() {
            var chips = resumir(form);
            chipsWrap.innerHTML = '';
            if (chips.length === 0) {
                var vazio = document.createElement('span');
                vazio.className = 'fb-filterbar__empty';
                vazio.textContent = 'Sem filtros ativos';
                chipsWrap.appendChild(vazio);
                botao.querySelector('span').textContent = 'Filtros';
                return;
            }
            chips.slice(0, 3).forEach(function (texto) {
                var chip = document.createElement('span');
                chip.className = 'fb-filterbar__chip';
                chip.textContent = texto;
                chipsWrap.appendChild(chip);
            });
            if (chips.length > 3) {
                var mais = document.createElement('span');
                mais.className = 'fb-filterbar__chip fb-filterbar__chip--more';
                mais.textContent = '+' + (chips.length - 3);
                chipsWrap.appendChild(mais);
            }
            botao.querySelector('span').textContent = 'Filtros · ' + chips.length;
        }

        function abrir() {
            form.classList.add('is-open');
            backdrop.classList.add('is-open');
        }
        function fechar() {
            form.classList.remove('is-open');
            backdrop.classList.remove('is-open');
        }

        botao.addEventListener('click', abrir);
        backdrop.addEventListener('click', fechar);
        form.addEventListener('submit', fechar);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { fechar(); }
        });

        atualizarChips();
    }

    function iniciar() {
        Array.prototype.forEach.call(
            document.querySelectorAll('form[data-fb-filters]'),
            montar
        );
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})();
