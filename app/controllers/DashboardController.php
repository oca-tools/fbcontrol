<?php
declare(strict_types=1);

class DashboardController extends Controller
{
    /**
     * Rota legada do Dashboard Geral: redireciona para o hub Análise preservando o filtro.
     */
    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $query = $_GET;
        unset($query['r']);
        $destino = '/?r=analise/index' . ($query !== [] ? '&' . http_build_query($query) : '');
        $this->redirect($destino);
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
