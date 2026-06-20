<?php
declare(strict_types=1);

class DashboardController extends Controller
{
    /**
     * Exibe o painel executivo de A&B com totais, fluxo por horário e últimos atendimentos.
     */
    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $this->view('dashboard/general', (new DashboardOperacionalService())->montarDashboardGeral($_GET));
    }

    /**
     * Exibe a leitura gerencial de um restaurante específico para acompanhamento do turno.
     */
    public function restaurant(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $restauranteId = (int)($_GET['id'] ?? 0);
        if ($restauranteId <= 0) {
            $this->redirect(InteligenciaOperacionalConstants::ROUTE_DASHBOARD_INDEX);
        }

        $resultado = (new DashboardOperacionalService())->montarDashboardRestaurante($restauranteId, $_GET);
        if (!$resultado->isSuccess()) {
            $this->redirect(InteligenciaOperacionalConstants::ROUTE_DASHBOARD_INDEX);
        }

        $this->view('dashboard/restaurant', $resultado->payload());
    }
}
