# Changelog

## FBControl 3.0

Release de polimento, escala e governanca.

### Principais entregas

- Redesign visual e responsivo dos modulos administrativos e tematicos.
- Logo e identidade visual renovadas para FBControl.
- Modulo de reservas tematicas redesenhado para cadastro, operacao, administracao e relatorios.
- Bloqueios de restaurante tematico por data e por dia da semana.
- Modo demonstracao para treinamento, restrito a administradores.
- Permissoes ajustadas para gerente A&B em modulos operacionais e administrativos relevantes.
- Usuarios com abas de ativos/desativados e melhor experiencia mobile.
- Relatorios e BI com exportacoes mais eficientes e paginacao server-side onde necessario.
- Filtros de dashboard/BI com preservacao de posicao de tela em fluxos extensos.
- Regras de fechamento automatico de turnos regulares e especiais.
- No-show automatico para reservas tematicas via cron.
- Upload de voucher reforcado, com limite operacional maior e compactacao quando possivel.
- LGPD revisada: aviso publico mais claro, retencao automatica restrita a tabelas permitidas e documentacao operacional alinhada.

### Performance

- Consultas criticas passaram a evitar `DATE(coluna)` em filtros indexaveis.
- Adicionados indices de apoio em auditoria, vouchers, turnos, acessos especiais, logs tematicos e refeicoes de colaboradores.
- Exports grandes passaram a usar streaming de CSV em rotas criticas.

### Seguranca

- Redirecionamentos locais sanitizados.
- Nomes de arquivos de download sanitizados.
- Upload de foto de perfil e voucher com validacoes reforcadas.
- Uso de `HTTP_HOST` removido de fallback sensivel de e-mail.
- Scanner SAST local ampliado para casos de host header e request URI.

### Operacao

- Healthcheck operacional CLI.
- Checagem de contexto de banco para evitar confusao entre bases locais.
- Runner unico multiplataforma para lint, smoke, contexto, higiene, healthcheck e SAST.
- Builder de pacote limpo, excluindo config local, uploads reais, backups e artefatos temporarios.

### Observacoes

- O banco local correto para validacao com dados importados e `controle_ab_vps`.
- O banco `controle_ab` pode existir como base antiga/de teste e nao deve ser usado para validar credenciais ou dados operacionais.
- Ha um alerta conhecido de e-mail ativo repetido para `hostessoca@gmail.com`; isso deve ser saneado na governanca de usuarios.
