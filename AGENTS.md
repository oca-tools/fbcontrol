# AGENTS.md

FBControl v3.0 — PHP 8 (custom MVC, no framework) + MySQL 8 + Bootstrap 5. There is no
Composer/npm; all dependencies are OS packages. Front controller is `public/index.php`
with `?r=controller/action` routing. See `README.md` for the canonical setup/run/lint docs.

## Cursor Cloud specific instructions

The system packages (PHP 8.3 + extensions, MySQL 8) and the dev database persist in the VM
snapshot. The update script does not install them or start services, so a fresh session
usually only needs the two startup steps below. Local config lives in
`config/config.local.php` (gitignored, persisted on disk) and points at the dev DB.

### Dev database (already provisioned in the snapshot)
- DB name `controle_ab`, user `controle_ab_user`, password `dev_password_123`, host `127.0.0.1`.
- Admin login for the app UI: `admin@oca-tools.com.br` / `Admin@123` (perfil `admin`).
- Schema was loaded from `sql/schema_v3_0.sql`. NOTE: that consolidated schema is missing the
  `reservas_tematicas_capacidades_datas` table, which only ships in
  `sql/migration_v2_5_tematic_capacity_by_date.sql`. That migration has been applied so
  `php tools/check_db_context.php` passes; re-apply it after any fresh schema reload.
- `tools/check_db_context.php` is intentionally strict: it requires the
  `reservas_tematicas_capacidades_datas` table AND exactly one active admin with email
  `admin@oca-tools.com.br`. Keep that single canonical admin row or this check (and
  `tools/run_checks.php`) will report FALHOU.

### Starting MySQL (no systemd in this container)
`systemctl`/`service` do not work here. Start the server manually and wait for it:
```bash
sudo mkdir -p /var/run/mysqld && sudo chown mysql:mysql /var/run/mysqld
sudo mysqld_safe --user=mysql &   # run in a tmux session so it outlives the shell
sudo mysqladmin ping              # repeat until "mysqld is alive"
```
The data directory is already initialized; do NOT re-run `mysqld --initialize`.

### Running the app (development)
```bash
APP_ENV=local php -S 0.0.0.0:8000 -t public
```
Then open `http://127.0.0.1:8000/?r=auth/login`. Always set `APP_ENV=local`; the web/CLI
SAPIs read DB + app config from env vars overridden by `config/config.local.php`. The login
form is CSRF-protected, so POSTing to `auth/login` with `curl` returns HTTP 419 — log in
through the browser instead.

### Lint / tests / health
- Full suite (PHP `-l` lint over `app|public|config|tools|deploy`, smoke, DB context, release
  hygiene, healthcheck, SAST, release candidate): `APP_ENV=local php tools/run_checks.php`.
- Non-destructive smoke (bootstrap + DB + key tables + layout render): `php tools/smoke_fbcontrol.php`.
- Ops healthcheck: `php tools/healthcheck_fbcontrol.php` (the `imagick` and `upload_max_filesize`
  warnings are expected in this dev setup and are non-blocking; `--strict` will fail on them).
- Optional cron features can be exercised directly, e.g. `php app/cron/auto_close_shifts.php`.

### Uploads
`public/uploads/{profiles,vouchers}` are gitignored. The update script recreates them; they
must be writable for voucher/profile photo features.
