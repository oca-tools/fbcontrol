<?php
$shiftModel = new ShiftModel();
$activeShift = $shiftModel->getActiveByUser($user['id']);
$canTematicas = in_array($user['perfil'], ['admin', 'supervisor', 'gerente'], true);
$canTematicasReserva = false;
$isHostessTematicoOnly = false;

if (!$canTematicas && $user['perfil'] === 'hostess') {
    $userRestaurantModel = new UserRestaurantModel();
    $assignedRests = $userRestaurantModel->byUser($user['id']);
    $hasTematico = false;
    $hasRegistroClassico = false;

    foreach ($assignedRests as $rest) {
        $restName = (string)($rest['nome'] ?? '');
        if (stripos($restName, 'Corais') !== false) {
            $canTematicas = true;
            $canTematicasReserva = true;
            $hasRegistroClassico = true;
        }

        $name = mb_strtolower(normalize_mojibake($restName), 'UTF-8');
        $isTematicoRest = (
            strpos($name, 'giardino') !== false
            || strpos($name, 'la brasa') !== false
            || strpos($name, "ix'u") !== false
            || strpos($name, 'ixu') !== false
            || strpos($name, 'ix') !== false
        );

        if ($isTematicoRest) {
            $canTematicas = true;
            $canTematicasReserva = true;
            $hasTematico = true;
        } else {
            $hasRegistroClassico = true;
        }
    }

    $isHostessTematicoOnly = $hasTematico && !$hasRegistroClassico;
}

$perfilLabelMap = [
    'admin' => 'Administrador',
    'supervisor' => 'Supervisor',
    'gerente' => 'Gerente',
    'hostess' => 'Hostess',
];
$perfilAtual = strtolower((string)($user['perfil'] ?? ''));
$perfilLabel = $perfilLabelMap[$perfilAtual] ?? ucfirst($perfilAtual);

$navIsActive = static function ($routes) use ($currentRoute): bool {
    $routes = is_array($routes) ? $routes : [$routes];
    foreach ($routes as $route) {
        $route = (string)$route;
        if ($route === $currentRoute) {
            return true;
        }
        if (substr($route, -2) === '/*') {
            $prefix = rtrim(substr($route, 0, -2), '/');
            $expectedPrefix = $prefix . '/';
            if ($prefix !== '' && strncmp($currentRoute, $expectedPrefix, strlen($expectedPrefix)) === 0) {
                return true;
            }
        }
    }
    return false;
};

$navAttrs = static function ($routes) use ($navIsActive): string {
    return $navIsActive($routes) ? 'class="nav-link active" aria-current="page"' : 'class="nav-link"';
};

$completedTurns = 0;
$level = null;

if ($perfilAtual === 'hostess') {
    $completedTurns = $shiftModel->countCompletedByUser($user['id']);
    $level = 'Bronze';
    if ($completedTurns >= 60) {
        $level = 'Platina';
    } elseif ($completedTurns >= 30) {
        $level = 'Ouro';
    } elseif ($completedTurns >= 10) {
        $level = 'Prata';
    }
}
