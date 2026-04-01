# OCA FBControl v2.0

Plataforma operacional A&B para hotéis e resorts, com foco em registro rápido de acesso, turnos, auditoria, relatórios e reservas temáticas.

## Stack
- PHP 8+ (MVC simples)
- MySQL 8+
- Bootstrap 5

## Módulos principais
- Login e perfis (`hostess`, `supervisor`, `gerente`, `admin`)
- Registro operacional por turno (UH, PAX, restaurante, porta, operação)
- Regras de duplicidade, fora de horário e múltiplo acesso
- Dashboard geral + centro de controle
- Relatórios operacionais e temáticos
- Vouchers + refeições de colaborador
- Reservas temáticas (reserva, operação e administração)
- KPIs estratégicos (v2.0)
- Onboarding/tutorial de hostess (v2.0)
- Envio de e-mail diário

## Instalação rápida
1. Execute `sql/schema_v1_1_final.sql`.
2. (v2.0) Execute `sql/migration_v2_0_onboarding_kpis.sql`.
3. (v2.0) Execute `sql/migration_v2_0_ocupacao_diaria.sql`.
4. Ajuste `config/config.php` (ou `config/config.local.php`).
5. Configure o servidor web apontando para `public`.
6. Acesse: `/?r=auth/login`.

## Rotas úteis
- `/?r=access/index` (registro/turno)
- `/?r=dashboard/index`
- `/?r=control/index`
- `/?r=relatorios/index`
- `/?r=relatoriosTematicos/index`
- `/?r=kpis/index` (novo)
- `/?r=emailRelatorios/index`

## Deploy VPS
- Script: `deploy/vps/install.sh`
- Guia: `docs/INSTALACAO_VPS.md`
