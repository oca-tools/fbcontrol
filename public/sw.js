/*
 * Service worker mínimo do FBControl (remake visual, etapa 8).
 * Existe para habilitar a instalação como app (PWA). Não intercepta nem cacheia
 * requisições de propósito: dados operacionais (turnos, reservas, acessos) devem
 * ser sempre frescos. Fila offline fica para uma fase futura.
 */
self.addEventListener('install', function () {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim());
});
