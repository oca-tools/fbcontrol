# Instalacao VPS - OCA FBControl

Guia para Ubuntu 24.04 (Apache + MySQL + PHP 8.3).

## 1) Preparar servidor

```bash
sudo apt update && sudo apt install -y git rsync unzip
cd /opt
sudo git clone <URL_DO_REPOSITORIO> ocafbcontrol
cd ocafbcontrol
```

## 2) Instalar aplicacao

```bash
sudo APP_DIR=/var/www/apps/fbcontrol/current \
DB_NAME=controle_ab \
DB_USER=controle_ab_user \
DB_PASS='SENHA_FORTE_AQUI' \
DOMAIN=fb.seudominio.com \
PHP_VERSION=8.3 \
APP_TIMEZONE=America/Sao_Paulo \
APP_SESSION_TIMEOUT_MIN=30 \
bash deploy/vps/install.sh
```

Opcional: aplicar hardening no final da instalacao.

```bash
sudo RUN_POST_HARDENING=yes bash deploy/vps/install.sh
```

## 3) SSL (producao)

```bash
sudo apt install -y certbot python3-certbot-apache
sudo certbot --apache -d fb.seudominio.com
```

## 4) Hardening de servidor (manual)

```bash
sudo SSH_PORT=22 DISABLE_SSH_PASSWORD_AUTH=no bash deploy/vps/hardening.sh
```

Se voce ja usa chave SSH e quer bloquear senha:

```bash
sudo DISABLE_SSH_PASSWORD_AUTH=yes bash deploy/vps/hardening.sh
```

## 5) Backup e restore drill

```bash
sudo APP_DIR=/var/www/apps/fbcontrol/current \
BACKUP_DIR=/var/backups/fbcontrol \
KEEP_DAYS=14 \
MYSQL_ROOT_PASS='SENHA_ROOT_MYSQL' \
bash deploy/vps/backup_restore_check.sh
```

## 6) Cron recomendados

Adicionar no root crontab (`sudo crontab -e`):

```bash
* * * * * /usr/bin/php /var/www/apps/fbcontrol/current/app/cron/auto_close_shifts.php >> /var/log/fbcontrol_shifts.log 2>&1
0 3 * * * /usr/bin/php /var/www/apps/fbcontrol/current/app/cron/lgpd_retention.php >> /var/log/fbcontrol_lgpd_retention.log 2>&1
30 3 * * * /var/www/apps/fbcontrol/current/deploy/vps/backup_restore_check.sh >> /var/log/fbcontrol_backup.log 2>&1
```

## 7) Validacao local de seguranca

No repositorio, rode:

```bash
bash deploy/security/run_security_checks.sh
```

## Observacoes

- O schema consolidado atual e `sql/schema_v2_1_final.sql`.
- Nao sobrescreva `config/config.local.php` em deploy incremental.
- `public/uploads/` deve permanecer persistente entre releases.
