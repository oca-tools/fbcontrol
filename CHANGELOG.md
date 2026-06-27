# Changelog

## FBControl 3.0

Release de polimento, escala e governanca.

### Principais entregas

- Redesign visual e responsivo dos modulos administrativos e tematicos.
- Logo e identidade visual renovadas para FBControl.
- Modulo de reservas tematicas redesenhado para cadastro, operacao, administracao e relatorios.
- Bloqueios de restaurante tematico por data e por dia da semana.
- Fechamento tematico por periodo de sete dias com abertura pontual de excecao.
- Disponibilidade tematica atualizada automaticamente ao trocar a data, sem reutilizar cache antigo.
- Redirecionamento pos-login alinhado ao perfil para evitar chegada indevida em pagina de acesso negado.
- Modo demonstracao para treinamento, restrito a administradores.
- Permissoes ajustadas para gerente A&B em modulos operacionais e administrativos relevantes.
- Usuarios com abas de ativos/desativados e melhor experiencia mobile.
- Relatorios e BI com exportacoes mais eficientes e paginacao server-side onde necessario.
- Exportacoes Excel profissionalizadas com workbook real, cabecalho visual, metadados e fallback compativel.
- Impressao operacional tematica redesenhada para leitura rapida por turno, PAX e observacoes.
- Filtros de dashboard/BI com preservacao de posicao de tela em fluxos extensos.
- Regras de fechamento automatico de turnos regulares e especiais.
- No-show automatico para reservas tematicas via cron.
- Upload de voucher reforcado, com limite operacional maior e compactacao quando possivel.
- LGPD revisada: aviso publico mais claro, retencao automatica restrita a tabelas permitidas e documentacao operacional alinhada.

### Performance

- Consultas criticas passaram a evitar `DATE(coluna)` em filtros indexaveis.
- Adicionados indices de apoio em auditoria, vouchers, turnos, acessos especiais, logs tematicos e refeicoes de colaboradores.
- Exports grandes passaram a usar streaming de CSV em rotas criticas.
- Exportacoes de acessos, vouchers, refeicoes de colaboradores e reservas tematicas passaram de `OFFSET` para cursor.
- Adicionados indices compostos para filtros de acessos e consultas de reservas tematicas.
- Incluida checagem CLI de indices, planos `EXPLAIN` e integridade das exportacoes em lotes.

### Seguranca

- Redirecionamentos locais sanitizados.
- Nomes de arquivos de download sanitizados.
- Upload de foto de perfil e voucher com validacoes reforcadas.
- Uso de `HTTP_HOST` removido de fallback sensivel de e-mail.
- Scanner SAST local ampliado para casos de host header e request URI.
- Auditoria passou a redigir senhas, tokens e outros segredos de forma recursiva.
- Adicionada ferramenta de saneamento de payloads historicos da auditoria.
- Eventos de seguranca anteriores ao login podem registrar `usuario_id` nulo.
- Sessoes autenticadas passam a revalidar periodicamente se o usuario continua ativo e com o mesmo perfil.
- Dados PHP incorporados em JavaScript usam codificacao segura para impedir encerramento de tag e XSS armazenado.
- Anexos de vouchers deixaram de ser servidos por URL publica direta e passam por rota autenticada.
- Caminhos de fotos e vouchers sao aceitos apenas dentro das categorias e extensoes de upload permitidas.
- Protecao contra clickjacking foi endurecida para impedir enquadramento da aplicacao.
- Testes automatizados cobrem JSON em contexto de script, URLs de upload, redirecionamentos e sessao desativada.
- Eventos internos de LGPD passaram a minimizar documentos, e-mails e textos livres.
- Formularios rapidos do modulo LGPD deixam de embutir dados pessoais em campos ocultos.
- Exportacao tabular de vouchers passa a indicar apenas se existe anexo, sem expor o caminho tecnico do arquivo.
- Adicionada ferramenta para sanear eventos LGPD historicos.
- Adicionados testes isolados para duplicidade, multiplo acesso, capacidade, permissoes e encerramento automatico.
- Regras tematicas e encerramento automatico passaram a usar services compartilhados.
- Scripts e estilos globais foram separados dos wrappers de layout.

### Operacao

- Healthcheck operacional CLI.
- Checagem de contexto de banco para evitar confusao entre bases locais.
- Runner unico multiplataforma para lint, smoke, contexto, higiene, healthcheck e SAST.
- Builder de pacote limpo, excluindo config local, uploads reais, backups e artefatos temporarios.

### Observacoes

- O banco local correto para validacao com dados importados e `controle_ab_vps`.
- O banco `controle_ab` pode existir como base antiga/de teste e nao deve ser usado para validar credenciais ou dados operacionais.
- Ha um alerta conhecido de e-mail ativo repetido para `hostessoca@gmail.com`; isso deve ser saneado na governanca de usuarios.
