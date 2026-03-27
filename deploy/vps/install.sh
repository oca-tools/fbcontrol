#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/ocafbcontrol}"
DOMAIN="${DOMAIN:-}"
DB_NAME="${DB_NAME:-controle_ab}"
DB_USER="${DB_USER:-controle_ab_user}"
DB_PASS="${DB_PASS:-troque_esta_senha}"
MYSQL_ROOT_PASS="${MYSQL_ROOT_PASS:-}"
PHP_VERSION="${PHP_VERSION:-8.3}"

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Execute como root: sudo bash deploy/vps/install.sh"
  exit 1
fi

echo "[1/7] Instalando pacotes base..."
apt update
apt install -y apache2 mysql-server php${PHP_VERSION} php${PHP_VERSION}-cli php${PHP_VERSION}-mysql php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml php${PHP_VERSION}-curl php${PHP_VERSION}-zip libapache2-mod-php${PHP_VERSION} unzip

echo "[2/7] Configurando banco de dados..."
MYSQL_CMD=(mysql -u root)
if [[ -n "$MYSQL_ROOT_PASS" ]]; then
  MYSQL_CMD+=("-p${MYSQL_ROOT_PASS}")
fi

"${MYSQL_CMD[@]}" <<SQL
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

echo "[3/7] Publicando aplicação em ${APP_DIR}..."
mkdir -p "${APP_DIR}"
rsync -av --delete --exclude='.git' --exclude='deploy' --exclude='docs' ./ "${APP_DIR}/"

if [[ ! -f "${APP_DIR}/config/config.local.php" ]]; then
  cp "${APP_DIR}/config/config.local.example.php" "${APP_DIR}/config/config.local.php"
fi

sed -i "s/'name' => 'controle_ab'/'name' => '${DB_NAME//\//\\/}'/" "${APP_DIR}/config/config.local.php"
sed -i "s/'user' => 'controle_ab_user'/'user' => '${DB_USER//\//\\/}'/" "${APP_DIR}/config/config.local.php"
sed -i "s/'pass' => 'troque_por_senha_forte'/'pass' => '${DB_PASS//\//\\/}'/" "${APP_DIR}/config/config.local.php"

echo "[4/7] Importando schema consolidado 1.1..."
SCHEMA_FILE="${APP_DIR}/sql/schema_v1_1_final.sql"
if [[ ! -f "$SCHEMA_FILE" ]]; then
  echo "Arquivo não encontrado: $SCHEMA_FILE"
  exit 1
fi
mysql -u "${DB_USER}" -p"${DB_PASS}" < "$SCHEMA_FILE"

echo "[5/7] Ajustando permissões..."
chown -R www-data:www-data "${APP_DIR}"
find "${APP_DIR}" -type d -exec chmod 755 {} \;
find "${APP_DIR}" -type f -exec chmod 644 {} \;
mkdir -p "${APP_DIR}/public/uploads/profiles" "${APP_DIR}/public/uploads/vouchers"
chmod -R 775 "${APP_DIR}/public/uploads"

echo "[6/7] Configurando Apache..."
cat > /etc/apache2/sites-available/ocafbcontrol.conf <<APACHE
<VirtualHost *:80>
    ServerName ${DOMAIN:-_}
    DocumentRoot ${APP_DIR}/public

    <Directory ${APP_DIR}/public>
        AllowOverride All
        Require all granted
        Options FollowSymLinks
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/ocafbcontrol_error.log
    CustomLog \${APACHE_LOG_DIR}/ocafbcontrol_access.log combined
</VirtualHost>
APACHE

a2enmod rewrite

a2dissite 000-default || true
a2ensite ocafbcontrol.conf
systemctl restart apache2

echo "[7/7] Concluído."
echo "URL: http://${DOMAIN:-SEU_IP_PUBLICO}/"
echo "Se usar domínio real, ative SSL com: certbot --apache -d seu-dominio"
