<?php
declare(strict_types=1);

final class TematicAccessService
{
    /**
     * Identifica restaurantes do modulo tematico pelo nome normalizado.
     *
     * @param string $name Nome do restaurante.
     * @return bool Verdadeiro quando o restaurante pertence ao modulo.
     */
    public static function isTematicRestaurant(string $name): bool
    {
        $name = self::normalize($name);
        foreach (AppConstants::TEMATIC_RESTAURANT_KEYWORDS as $keyword) {
            if (strpos($name, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Identifica o restaurante Corais pelo nome.
     *
     * @param string $name Nome do restaurante.
     * @return bool Verdadeiro quando o nome corresponde ao Corais.
     */
    public static function isCorais(string $name): bool
    {
        return strpos(self::normalize($name), AppConstants::TEMATIC_CORAIS_KEYWORD) !== false;
    }

    /**
     * Identifica o restaurante La Brasa pelo nome.
     *
     * @param string $name Nome do restaurante.
     * @return bool Verdadeiro quando o nome corresponde ao La Brasa.
     */
    public static function isLaBrasa(string $name): bool
    {
        return strpos(self::normalize($name), 'la brasa') !== false;
    }

    /**
     * Identifica operacoes tematicas pelo nome.
     *
     * @param string $name Nome da operacao.
     * @return bool Verdadeiro quando a operacao e tematica.
     */
    public static function isTematicOperation(string $name): bool
    {
        return strpos(self::normalize($name), AppConstants::TEMATIC_OPERATION_KEYWORD) !== false;
    }

    /**
     * Verifica se um turno pertence ao fluxo tematico.
     *
     * @param array $shift Dados do turno.
     * @return bool Verdadeiro quando o turno e tematico.
     */
    public static function isTematicShift(array $shift): bool
    {
        $restaurant = (string)($shift['restaurante'] ?? '');
        if (!self::isTematicRestaurant($restaurant)) {
            return false;
        }

        if (self::isLaBrasa($restaurant)) {
            return self::isTematicOperation((string)($shift['operacao'] ?? ''));
        }

        return true;
    }

    /**
     * Filtra apenas restaurantes tematicos.
     *
     * @param array $restaurants Lista de restaurantes.
     * @return array Restaurantes classificados como tematicos.
     */
    public static function filterTematicRestaurants(array $restaurants): array
    {
        return array_values(array_filter(
            $restaurants,
            static fn(array $restaurant): bool => self::isTematicRestaurant(
                (string)($restaurant['nome'] ?? '')
            )
        ));
    }

    /**
     * Retorna todos os restaurantes tematicos cadastrados.
     *
     * @return array Restaurantes tematicos.
     */
    public function allTematicRestaurants(): array
    {
        return self::filterTematicRestaurants((new RestaurantModel())->all());
    }

    /**
     * Retorna restaurantes atribuidos a uma hostess.
     *
     * @param int $userId Identificador do usuario.
     * @return array Restaurantes atribuidos.
     */
    public function assignedRestaurants(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }
        return (new UserRestaurantModel())->byUser($userId);
    }

    /**
     * Retorna os restaurantes tematicos atribuidos a um usuario.
     *
     * @param int $userId Identificador do usuario.
     * @return array Restaurantes tematicos atribuidos.
     */
    public function tematicRestaurantsForUser(int $userId): array
    {
        return self::filterTematicRestaurants($this->assignedRestaurants($userId));
    }

    /**
     * Indica se a hostess possui acesso ao Corais.
     *
     * @param int $userId Identificador do usuario.
     * @return bool Verdadeiro quando ha atribuicao ao Corais.
     */
    public function hostessHasCorais(int $userId): bool
    {
        foreach ($this->assignedRestaurants($userId) as $restaurant) {
            if (self::isCorais((string)($restaurant['nome'] ?? ''))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Indica se a hostess possui algum restaurante tematico atribuido.
     *
     * @param int $userId Identificador do usuario.
     * @return bool Verdadeiro quando ha atribuicao tematica.
     */
    public function hostessHasTematicRestaurant(int $userId): bool
    {
        return $this->tematicRestaurantsForUser($userId) !== [];
    }

    /**
     * Verifica se o usuario pode acessar o modulo de reservas tematicas.
     *
     * @param array $user Dados do usuario autenticado.
     * @return bool Verdadeiro quando o acesso e permitido.
     */
    public function canAccessModule(array $user): bool
    {
        $role = (string)($user['perfil'] ?? '');
        if (in_array($role, AppConstants::MANAGEMENT_ROLES, true)) {
            return true;
        }
        if ($role !== AppConstants::ROLE_HOSTESS) {
            return false;
        }

        $userId = (int)($user['id'] ?? 0);
        return $this->hostessHasCorais($userId) || $this->hostessHasTematicRestaurant($userId);
    }

    /**
     * Sanitiza os filtros de consulta/exportação dos relatórios temáticos.
     * Fonte única usada pelo hub Análise (aba Temáticos) e pela exportação.
     *
     * @param array $tematicos Restaurantes temáticos permitidos.
     * @param bool $defaultDate Aplica a data de hoje quando nenhuma data for informada.
     * @return array Filtros sanitizados.
     */
    public static function lerFiltrosRelatorio(array $tematicos, bool $defaultDate = false): array
    {
        $status = normalize_mojibake(trim((string)($_GET['status'] ?? '')));
        if (mb_strlen($status, 'UTF-8') > 40) {
            $status = mb_substr($status, 0, 40, 'UTF-8');
        }
        $grupoNome = normalize_mojibake(trim((string)($_GET['grupo_nome'] ?? '')));
        if (mb_strlen($grupoNome, 'UTF-8') > 120) {
            $grupoNome = mb_substr($grupoNome, 0, 120, 'UTF-8');
        }
        $query = normalize_mojibake(trim((string)($_GET['q'] ?? '')));
        if (mb_strlen($query, 'UTF-8') > 120) {
            $query = mb_substr($query, 0, 120, 'UTF-8');
        }

        $dataInicio = sanitize_date_param($_GET['data_inicio'] ?? '');
        $dataFim = sanitize_date_param($_GET['data_fim'] ?? '');
        $data = sanitize_date_param($_GET['data'] ?? '', $defaultDate ? date('Y-m-d') : '');
        if ($dataInicio !== '' && $dataFim !== '') {
            $data = '';
        }

        return [
            'data' => $data,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'restaurante_id' => sanitize_int_param($_GET['restaurante_id'] ?? ''),
            'turno_id' => sanitize_int_param($_GET['turno_id'] ?? ''),
            'status' => $status,
            'grupo_nome' => $grupoNome,
            'q' => $query,
            'restaurante_ids' => array_map(static fn($r) => (int)$r['id'], $tematicos),
        ];
    }

    /**
     * Normaliza texto para comparacoes sem acentos e caixa.
     *
     * @param string $value Texto original.
     * @return string Texto normalizado.
     */
    private static function normalize(string $value): string
    {
        $value = mb_strtolower(normalize_mojibake($value), 'UTF-8');
        return strtr($value, [
            'á' => 'a',
            'à' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'é' => 'e',
            'ê' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ú' => 'u',
            'ç' => 'c',
        ]);
    }
}
