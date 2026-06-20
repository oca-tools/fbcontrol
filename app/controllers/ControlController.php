<?php
class ControlController extends Controller
{
    public function index(): void
    {
        $this->autorizarGestaoOperacional();

        $painelOperacao = (new ControlDashboardService())->montarPainelOperacao(
            date('Y-m-d'),
            $this->paginaSolicitada()
        );

        $this->view('control/index', $painelOperacao);
    }

    private function autorizarGestaoOperacional(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);
    }

    private function paginaSolicitada(): int
    {
        return max(1, (int)($_GET['page'] ?? 1));
    }
}
