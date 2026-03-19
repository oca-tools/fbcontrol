# Instalação VPS - OCA FBControl

Este guia instala o sistema em Ubuntu 22.04/24.04 com Apache + MySQL + PHP.

## 1) Preparar servidor

```bash
sudo apt update && sudo apt install -y git rsync
cd /opt
sudo git clone <URL_DO_REPOSITORIO> ocafbcontrol
cd ocafbcontrol
```

## 2) Rodar instalador automático

```bash
sudo bash deploy/vps/install.sh
```

Com parâmetros personalizados:

```bash
sudo APP_DIR=/var/www/ocafbcontrol \
DB_NAME=controle_ab \
DB_USER=controle_ab_user \
DB_PASS='SENHA_FORTE_AQUI' \
DOMAIN=ab.seudominio.com \
bash deploy/vps/install.sh
```

## 3) Login inicial

Use o usuário admin já cadastrado no banco (ou crie via SQL, se necessário).

## 4) SSL (produção)

```bash
sudo apt install -y certbot python3-certbot-apache
sudo certbot --apache -d ab.seudominio.com
```

## 5) Atualização futura

```bash
cd /opt/ocafbcontrol
sudo git pull
```

Para atualização em ambiente já em produção, aplique somente scripts de atualização planejados.
O `install.sh` atual é voltado para **instalação inicial limpa**.

## Observações

- O instalador usa `config/config.local.php` para credenciais locais do VPS.
- A instalação inicial usa **um único arquivo SQL consolidado**: `sql/schema_v1_0_final.sql`.
- As migrações antigas foram mantidas apenas como histórico de evolução.
- O arquivo base `config/config.php` continua intacto e com fallback para variáveis de ambiente.
- Uploads ficam em `public/uploads/`.
