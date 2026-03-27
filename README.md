# OCA FBControl v1.0

Sistema web para registro de acessos de hóspedes (PAX) por Unidade Habitacional (UH) em restaurantes e áreas do hotel.

## Requisitos
- PHP 8+
- MySQL 8+

## Instalação rápida
1. Crie o banco e tabelas com o script `sql/schema_v1_1_final.sql`.
2. Ajuste as credenciais em `config/config.php`.
3. Gere o hash da senha do admin e substitua no `sql/schema_v1_1_final.sql`.
4. Aponte o servidor para a pasta `public`.

## Rotas principais
- `/?r=auth/login`
- `/?r=access/index`
- `/?r=dashboard/index`
- `/?r=restaurantes/index`
- `/?r=portas/index`
- `/?r=operacoes/index`
- `/?r=usuarios/index`

## API (futuro)
Endpoint de exemplo: `/?r=api/ping` (retorna JSON).
