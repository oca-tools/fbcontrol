#!/usr/bin/env bash
set -euo pipefail

SSH_PORT="${SSH_PORT:-22}"
DISABLE_SSH_PASSWORD_AUTH="${DISABLE_SSH_PASSWORD_AUTH:-no}"
ENABLE_UNATTENDED_UPGRADES="${ENABLE_UNATTENDED_UPGRADES:-yes}"

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Execute como root: sudo bash deploy/vps/hardening.sh"
  exit 1
fi

set_sshd_option() {
  local key="$1"
  local value="$2"
  local file="/etc/ssh/sshd_config"
  if grep -qE "^[[:space:]]*#?[[:space:]]*${key}[[:space:]]+" "$file"; then
    sed -ri "s|^[[:space:]]*#?[[:space:]]*${key}[[:space:]]+.*|${key} ${value}|g" "$file"
  else
    echo "${key} ${value}" >>"$file"
  fi
}

echo "[1/5] Instalando pacotes de seguranca..."
apt update
apt install -y ufw fail2ban unattended-upgrades apt-listchanges

echo "[2/5] Configurando firewall (UFW)..."
ufw --force reset
ufw default deny incoming
ufw default allow outgoing
ufw allow "${SSH_PORT}/tcp"
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

echo "[3/5] Configurando SSH e Fail2Ban..."
cp -n /etc/ssh/sshd_config /etc/ssh/sshd_config.bak
set_sshd_option "PermitRootLogin" "no"
set_sshd_option "MaxAuthTries" "4"
set_sshd_option "LoginGraceTime" "30"
set_sshd_option "ClientAliveInterval" "300"
set_sshd_option "ClientAliveCountMax" "2"
set_sshd_option "LogLevel" "VERBOSE"
if [[ "${DISABLE_SSH_PASSWORD_AUTH}" == "yes" ]]; then
  set_sshd_option "PasswordAuthentication" "no"
  set_sshd_option "KbdInteractiveAuthentication" "no"
  set_sshd_option "ChallengeResponseAuthentication" "no"
  set_sshd_option "PubkeyAuthentication" "yes"
fi
sshd -t
systemctl restart ssh || systemctl restart sshd

cat >/etc/fail2ban/jail.d/ocafbcontrol.local <<'EOF'
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5
backend = systemd

[sshd]
enabled = true
port = ssh
logpath = %(sshd_log)s

[apache-auth]
enabled = true
port = http,https
logpath = /var/log/apache2/*error.log
maxretry = 6
EOF

systemctl enable fail2ban
systemctl restart fail2ban

echo "[4/5] Aplicando hardening de kernel/rede..."
cat >/etc/sysctl.d/99-ocafbcontrol-hardening.conf <<'EOF'
net.ipv4.tcp_syncookies = 1
net.ipv4.conf.all.accept_redirects = 0
net.ipv4.conf.default.accept_redirects = 0
net.ipv4.conf.all.send_redirects = 0
net.ipv4.conf.default.send_redirects = 0
net.ipv4.conf.all.accept_source_route = 0
net.ipv4.conf.default.accept_source_route = 0
net.ipv4.conf.all.log_martians = 1
net.ipv4.conf.default.log_martians = 1
EOF
sysctl --system >/dev/null

if [[ "${ENABLE_UNATTENDED_UPGRADES}" == "yes" ]]; then
  dpkg-reconfigure -f noninteractive unattended-upgrades >/dev/null 2>&1 || true
fi

echo "[5/5] Status final..."
ufw status verbose
fail2ban-client status sshd || true
echo "Hardening base concluido."
