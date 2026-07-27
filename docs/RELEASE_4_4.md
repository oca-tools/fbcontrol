# FBControl 4.4 - Reinicio operacional

## Objetivo

A versao 4.4 retoma o FBControl como ambiente operacional, removendo a pagina publica
de agradecimento e direcionando a raiz do dominio para o login. Ela tambem consolida as
melhorias de rastreabilidade das reservas, incluindo tentativas recusadas e protecao
contra reenvio depois de queda de conexao.

## Banco novo

Para uma instalacao realmente nova, utilize `sql/schema_v4_0.sql`.

Para reiniciar uma operacao existente sem perder a estrutura do resort, use
`tools/reset_operational_data.php`. A ferramenta preserva restaurantes, portas,
operacoes, turnos tematicos, capacidades, UHs, configuracoes de e-mail/LGPD,
fechamentos semanais e o administrador informado. Ela elimina os dados operacionais,
historicos, usuarios restantes e anexos de vouchers/perfis apos um backup completo.

## Procedimento seguro de producao

1. Empacote a release com `php tools/build_release.php 4.4`.
2. Envie o pacote e `deploy/vps/deploy_v4_reset.sh` ao VPS.
3. Execute o deploy usando a confirmacao explicita `RESET_FBCONTROL_V4_4`.
4. Confirme o login do administrador, os tres restaurantes tematicos, turnos,
   capacidades e UHs antes de liberar a operacao.
5. Mantenha o backup de banco e da release anterior ate concluir o primeiro dia de uso.

O script de deploy nunca remove releases anteriores sem `PRUNE_OLD_RELEASES=1`.

## Validacao

```bash
php tools/run_checks.php
php tools/check_release_candidate.php 4.4
php tools/build_release.php 4.4 ignored.tar.gz --dry-run
```

## Versao

Versao de aplicacao: `4.4`.
