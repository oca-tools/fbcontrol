# FBControl - Inventario de arquivos .bak

Este inventario lista os backups encontrados dentro da release ativa local e movidos para arquivo externo.

Status: movidos em 2026-04-27, sem apagar arquivos.

## Resumo

- Total: 41 arquivos.
- Tamanho aproximado: 1,8 MB.
- Origem: `apps/fbcontrol/releases/20260410_144241`.
- Destino: `apps/fbcontrol/_archive_bak_20260427`.
- Arquivos restantes na release apos a limpeza: 0.

## Arquivos

| Caminho | Tamanho |
| --- | ---: |
| `app/controllers/AccessController.php.bak.20260421_121431` | 46561 |
| `app/controllers/AccessController.php.bak.20260421_135334` | 46561 |
| `app/controllers/LgpdController.php.bak.20260420_163011` | 17419 |
| `app/controllers/LgpdController.php.bak.20260421_121431` | 17296 |
| `app/controllers/LgpdController.php.bak.20260421_135334` | 17296 |
| `app/controllers/RelatoriosTematicosController.php.bak.20260420_163639` | 6067 |
| `app/controllers/RelatoriosTematicosController.php.bak.20260421_121431` | 6089 |
| `app/controllers/RelatoriosTematicosController.php.bak.20260421_135334` | 6089 |
| `app/controllers/ReservasTematicasController.php.bak.20260421_121431` | 60760 |
| `app/controllers/ReservasTematicasController.php.bak.20260421_135334` | 61533 |
| `app/helpers/functions.php.bak.2026-04-10_144846` | 5709 |
| `app/models/AccessModel.php.bak.20260421_121431` | 42387 |
| `app/models/AccessModel.php.bak.20260421_135334` | 42387 |
| `app/models/ReservaTematicaModel.php.bak.20260421_121431` | 56897 |
| `app/models/ReservaTematicaModel.php.bak.20260421_135334` | 56897 |
| `app/models/ReservaTematicaTurnoModel.php.bak.20260421_121431` | 3945 |
| `app/models/ReservaTematicaTurnoModel.php.bak.20260421_135334` | 5095 |
| `app/models/UnitModel.php.bak.20260422_085621` | 6098 |
| `app/views/access/index.php.bak.20260421_121431` | 51044 |
| `app/views/access/index.php.bak.20260421_135334` | 51044 |
| `app/views/dashboard/general.php.bak.20260421_122810` | 21393 |
| `app/views/dashboard/general.php.bak.20260421_122819` | 21435 |
| `app/views/dashboard/general.php.bak.20260421_135334` | 21435 |
| `app/views/dashboard/index.php.bak.20260421_122810` | 5634 |
| `app/views/dashboard/index.php.bak.20260421_122819` | 5630 |
| `app/views/dashboard/index.php.bak.20260421_135334` | 5630 |
| `app/views/dashboard/restaurant.php.bak.20260421_122810` | 24060 |
| `app/views/dashboard/restaurant.php.bak.20260421_122819` | 24102 |
| `app/views/dashboard/restaurant.php.bak.20260421_135334` | 24102 |
| `app/views/kpis/index.php.bak.20260421_151617` | 27843 |
| `app/views/partials/header.php.bak.20260420_163011` | 126276 |
| `app/views/partials/header.php.bak.20260420_163639` | 128228 |
| `app/views/partials/header.php.bak.20260420_194154` | 129864 |
| `app/views/partials/header.php.bak.20260420_194204` | 129886 |
| `app/views/partials/header.php.bak.20260421_121431` | 129886 |
| `app/views/partials/header.php.bak.20260421_135334` | 130695 |
| `app/views/partials/header.php.bak.20260421_152351` | 130911 |
| `app/views/relatorios_tematicos/index.php.bak.20260421_121431` | 26888 |
| `app/views/relatorios_tematicos/index.php.bak.20260421_135334` | 28439 |
| `app/views/reservas_tematicas/reservas.php.bak.20260421_121431` | 31121 |
| `app/views/reservas_tematicas/reservas.php.bak.20260421_135334` | 38611 |

## Recomendacao

Manter estes arquivos fora da release operacional antes de qualquer novo deploy.

Destino usado:

`apps/fbcontrol/_archive_bak_20260427`

Antes de replicar a limpeza no VPS, confirmar se existe algum processo manual do servidor que ainda depende desses backups.
