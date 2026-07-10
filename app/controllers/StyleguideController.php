<?php
declare(strict_types=1);

class StyleguideController extends Controller
{
    /**
     * Exibe a galeria interna de componentes do remake visual (tokens + components.css).
     * Ferramenta de validação da fundação visual; acesso restrito à administração.
     */
    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);

        $this->view('styleguide/index');
    }
}
