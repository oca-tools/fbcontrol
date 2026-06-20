<?php
declare(strict_types=1);

class PrivacidadeController extends Controller
{
    /**
     * Publica a política de privacidade e canais do titular sem exigir autenticação administrativa.
     */
    public function index(): void
    {
        $config = [
            'controlador_nome' => GovernancaConstants::DEFAULT_CONTROLADOR_NOME,
            'controlador_email' => '',
            'encarregado_nome' => '',
            'encarregado_email' => '',
            'encarregado_telefone' => '',
            'canal_titular_url' => GovernancaConstants::ROUTE_PRIVACIDADE_INDEX,
            'canal_titular_email' => '',
            'politica_privacidade_url' => GovernancaConstants::ROUTE_PRIVACIDADE_INDEX,
            'prazo_titular_dias' => GovernancaConstants::PRAZO_TITULAR_DIAS,
            'prazo_incidente_dias_uteis' => GovernancaConstants::PRAZO_INCIDENTE_DIAS_UTEIS,
        ];

        try {
            $model = new LgpdModel();
            $dbConfig = $model->getConfig();
            if (!empty($dbConfig)) {
                $config = array_merge($config, $dbConfig);
            }
        } catch (Throwable $e) {
            (new SegurancaRepository())->registrarLogSegurancaSeguro(
                GovernancaConstants::AUDIT_LGPD_PANEL_LOAD_FAILED,
                null,
                ['route' => 'privacidade/index']
            );
        }

        $this->view('public/privacidade', [
            'config' => $config,
            'flash' => get_flash(),
        ]);
    }
}
