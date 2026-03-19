<?php
class ApiController extends Controller
{
    // Endpoint de exemplo para futura integração via API.
    public function ping(): void
    {
        $this->requireAuth();
        json_response([
            'status' => 'ok',
            'sistema' => 'Sistema de Controle A&B',
            'timestamp' => date('c'),
        ]);
    }
}
