<?php
class EspeciaisController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        set_flash('info', 'Serviços especiais foram unificados ao registro padrão.');
        $this->redirect('/?r=access/index');
    }

    public function start(): void
    {
        $this->requireAuth();
        set_flash('info', 'Serviços especiais foram unificados ao registro padrão.');
        $this->redirect('/?r=access/index');
    }

    public function register(): void
    {
        $this->requireAuth();
        set_flash('info', 'Serviços especiais foram unificados ao registro padrão.');
        $this->redirect('/?r=access/index');
    }
}
