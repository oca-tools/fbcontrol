# Security Baseline - 2026-04-05

Escopo: repositorio `C:/Users/MATIAS/Documents/aeb`.

## Passos 1-6 executados

1. Script de hardening VPS criado: `deploy/vps/hardening.sh`.
2. Script de backup + restore drill criado: `deploy/vps/backup_restore_check.sh`.
3. Scanner SAST baseline criado: `deploy/security/sast_scan.php`.
4. Runner de checks de seguranca criado: `deploy/security/run_security_checks.sh`.
5. Instalador VPS reforcado (`deploy/vps/install.sh`) com:
   - variaveis de ambiente do app;
   - permissao restrita para `config/config.local.php`;
   - conf Apache para `SetEnv` com dono root.
6. Documentacao operacional atualizada:
   - `docs/INSTALACAO_VPS.md`
   - `docs/LGPD_OPERACAO.md`

## Evidencias de verificacao local

Comandos rodados localmente:

```bash
php -l <todos os arquivos php de app/public/config>
php deploy/security/sast_scan.php
```

Resultado obtido em 2026-04-05:
- PHP lint: `OK: 92 arquivos PHP sem erro de sintaxe`.
- SAST baseline: `CRITICAL=0`, `HIGH=0`, `MEDIUM=0`, `LOW=0`.

Para go-live:
- aplicar scripts de hardening e backup no VPS;
- validar restore drill com credencial root MySQL;
- manter execucao diaria dos crons LGPD e backup.

## Pendencias de producao (fora do repo)

- Aplicar `deploy/vps/hardening.sh` no servidor.
- Ativar cron de `lgpd_retention.php` e `backup_restore_check.sh`.
- Validar restauracao mensal de backup em banco temporario.
- Revisar certificado TLS e politica de renovacao.
- Garantir MFA nas contas administrativas (GitHub, provedor VPS, DNS, e-mail).

## Limites desta baseline

- Scanner SAST e de baseline (regex), nao substitui pentest manual.
- Nao inclui DAST autenticado.
- Nao inclui analise de infraestrutura de nuvem/provedor.
