<?php
declare(strict_types=1);

class OperacaoController extends Controller
{
    /**
     * Hub Operação (remake visual, etapa 6): monitor do dia em tempo real.
     * Absorve o Centro de Controle; o detalhe por restaurante segue em dashboard/restaurant.
     */
    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $painelOperacao = (new ControlDashboardService())->montarPainelOperacao(
            date('Y-m-d'),
            max(1, (int)($_GET['page'] ?? 1))
        );

        $this->view('operacao/index', $painelOperacao);
    }
}
