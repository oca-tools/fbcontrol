<?php
class PrivacidadeController extends Controller
{
    public function index(): void
    {
        $config = [
            'controlador_nome' => 'Grand Oca Maragogi Resort',
            'controlador_email' => '',
            'encarregado_nome' => '',
            'encarregado_email' => '',
            'encarregado_telefone' => '',
            'canal_titular_url' => '/?r=privacidade/index',
            'canal_titular_email' => '',
            'politica_privacidade_url' => '/?r=privacidade/index',
            'prazo_titular_dias' => 15,
            'prazo_incidente_dias_uteis' => 3,
        ];

        try {
            $model = new LgpdModel();
            $dbConfig = $model->getConfig();
            if (!empty($dbConfig)) {
                $config = array_merge($config, $dbConfig);
            }
        } catch (Throwable $e) {
            // Se ainda não existe estrutura LGPD no banco, exibe versão padrão.
        }

        $this->view('public/privacidade', [
            'config' => $config,
            'flash' => get_flash(),
        ]);
    }
}
