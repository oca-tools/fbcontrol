<?php
class ControlController extends Controller
{
    /**
     * Rota legada do Centro de Controle: redireciona para o hub Operação preservando a paginação.
     */
    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $query = $_GET;
        unset($query['r']);
        $destino = '/?r=operacao/index' . ($query !== [] ? '&' . http_build_query($query) : '');
        $this->redirect($destino);
    }
}
