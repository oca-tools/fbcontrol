<?php
declare(strict_types=1);

final class HomeController extends Controller
{
    public function index(): void
    {
        $isAuthenticated = Auth::check();
        header($isAuthenticated
            ? 'Cache-Control: private, no-store, max-age=0'
            : 'Cache-Control: public, max-age=300, stale-while-revalidate=60');
        $stats = (new HomeImpactService())->summary();
        require __DIR__ . '/../views/public/home.php';
    }
}
