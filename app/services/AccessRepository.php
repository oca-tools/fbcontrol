<?php
declare(strict_types=1);

final class AccessRepository extends RepositoryBase implements AccessRepositoryInterface
{
    /**
     * Persiste a entrada de hóspedes no salão e devolve alertas operacionais
     * usados pela tela do turno, como duplicidade e acesso fora do horário.
     */
    public function registrarEntradaSalao(array $entradaSalao, int $usuarioId): array
    {
        $uh = $this->buscarUhPorNumero((string)($entradaSalao['uh_numero'] ?? ''));
        if (!$uh) {
            return ['id' => 0, 'error' => ControleSalaoConstants::CODE_UH_INVALIDA];
        }

        $regraDeHorario = $this->buscarRegraDeHorarioDaOperacao(
            (int)$entradaSalao['restaurante_id'],
            (int)$entradaSalao['operacao_id']
        );
        $uhTecnica = $this->ehUhTecnica((string)($uh['numero'] ?? ''));
        $possivelDuplicidade = !$uhTecnica && $this->existeDuplicidadeDeDezMinutos(
            (int)$uh['id'],
            (int)$entradaSalao['restaurante_id'],
            (int)$entradaSalao['operacao_id'],
            (int)$entradaSalao['pax']
        );
        $foraDoHorario = $this->entradaEstaForaDoHorarioDaOperacao($regraDeHorario);

        $stmt = $this->db->prepare("
            INSERT INTO acessos
            (turno_id, uh_id, pax, restaurante_id, porta_id, operacao_id, alerta_duplicidade, fora_do_horario, criado_em, usuario_id)
            VALUES
            (:turno_id, :uh_id, :pax, :restaurante_id, :porta_id, :operacao_id, :alerta_duplicidade, :fora_do_horario, NOW(), :usuario_id)
        ");
        $stmt->execute([
            ':turno_id' => $entradaSalao['turno_id'] ?? null,
            ':uh_id' => (int)$uh['id'],
            ':pax' => (int)$entradaSalao['pax'],
            ':restaurante_id' => (int)$entradaSalao['restaurante_id'],
            ':porta_id' => $entradaSalao['porta_id'] ?? null,
            ':operacao_id' => (int)$entradaSalao['operacao_id'],
            ':alerta_duplicidade' => $possivelDuplicidade
                ? ControleSalaoConstants::SQL_TRUE
                : ControleSalaoConstants::SQL_FALSE,
            ':fora_do_horario' => $foraDoHorario
                ? ControleSalaoConstants::SQL_TRUE
                : ControleSalaoConstants::SQL_FALSE,
            ':usuario_id' => $usuarioId,
        ]);

        return [
            'id' => (int)$this->db->lastInsertId(),
            'alerta_duplicidade' => $possivelDuplicidade,
            'fora_do_horario' => $foraDoHorario,
        ];
    }

    /**
     * Calcula quanto PAX da UH ja foi consumido na mesma operacao do dia,
     * evitando que uma entrada ultrapasse a capacidade operacional do quarto.
     */
    public function totalPaxDaUhNaOperacaoDoDia(string $uhNumero, int $operacaoId, string $dataOperacao): int
    {
        [$inicioDoDia, $fimDoDia] = $this->intervaloDoDia($dataOperacao);
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(a.pax), 0) AS total_pax
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            WHERE uh.numero = :uh
              AND a.operacao_id = :operacao_id
              AND a.criado_em >= :inicio_do_dia
              AND a.criado_em < :fim_do_dia
        ");
        $stmt->execute([
            ':uh' => $uhNumero,
            ':operacao_id' => $operacaoId,
            ':inicio_do_dia' => $inicioDoDia,
            ':fim_do_dia' => $fimDoDia,
        ]);
        $row = $stmt->fetch();
        return (int)($row['total_pax'] ?? 0);
    }

    /**
     * Verifica se a hostess acabou de lançar o mesmo grupo no mesmo turno,
     * exigindo confirmação explícita para evitar entradas acidentais em sequência.
     */
    public function existeDuplicidadeImediata(
        string $uhNumero,
        int $restauranteId,
        int $operacaoId,
        int $pax,
        int $turnoId,
        int $usuarioId
    ): bool {
        $uhNumero = trim($uhNumero);
        if ($uhNumero === '' || $pax <= 0 || $this->ehUhTecnica($uhNumero)) {
            return false;
        }

        $uh = $this->buscarUhPorNumero($uhNumero);
        if (!$uh) {
            return false;
        }

        $stmt = $this->db->prepare("
            SELECT a.id
            FROM acessos a
            WHERE a.uh_id = :uh_id
              AND a.restaurante_id = :restaurante_id
              AND a.operacao_id = :operacao_id
              AND a.turno_id = :turno_id
              AND a.usuario_id = :usuario_id
              AND a.pax = :pax
              AND a.criado_em >= (NOW() - INTERVAL " . ControleSalaoConstants::IMMEDIATE_DUPLICATE_WINDOW_MINUTES . " MINUTE)
            ORDER BY a.id DESC
            LIMIT 1
        ");
        $stmt->execute([
            ':uh_id' => (int)$uh['id'],
            ':restaurante_id' => $restauranteId,
            ':operacao_id' => $operacaoId,
            ':turno_id' => $turnoId,
            ':usuario_id' => $usuarioId,
            ':pax' => $pax,
        ]);

        return (bool)$stmt->fetch();
    }

    /**
     * Conta os acessos registrados no turno para bloquear cancelamento quando
     * ja houve movimentação real no salão.
     */
    public function quantidadeLancamentosDoTurno(int $turnoId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM acessos WHERE turno_id = :turno_id");
        $stmt->execute([':turno_id' => $turnoId]);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    /**
     * Lista os lançamentos mais recentes do salão com indicadores operacionais
     * prontos para o painel de acompanhamento.
     */
    public function ultimosLancamentosDoSalao(int $limit = ControleSalaoConstants::DEFAULT_ACCESS_LIST_LIMIT): array
    {
        $limit = max(
            ControleSalaoConstants::MIN_ACCESS_LIST_LIMIT,
            min(ControleSalaoConstants::MAX_ACCESS_LIST_LIMIT, $limit)
        );
        $statusOperacionalSql = $this->statusOperacionalSql('a');
        $multiploAcessoSql = $this->multiploAcessoSql('a');
        $stmt = $this->db->prepare("
            SELECT a.*, uh.numero AS uh_numero, r.nome AS restaurante, p.nome AS porta, o.nome AS operacao, u.nome AS usuario,
                   ({$multiploAcessoSql}) AS multiplo_acesso,
                   ({$statusOperacionalSql}) AS status_operacional
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN restaurantes r ON r.id = a.restaurante_id
            LEFT JOIN portas p ON p.id = a.porta_id
            JOIN operacoes o ON o.id = a.operacao_id
            JOIN usuarios u ON u.id = a.usuario_id
            ORDER BY a.criado_em DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function buscarUhPorNumero(string $uhNumero): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM unidades_habitacionais WHERE numero = :numero LIMIT 1");
        $stmt->execute([':numero' => trim($uhNumero)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function buscarRegraDeHorarioDaOperacao(int $restauranteId, int $operacaoId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM restaurante_operacoes
            WHERE restaurante_id = :restaurante_id
              AND operacao_id = :operacao_id
              AND ativo = 1
            LIMIT 1
        ");
        $stmt->execute([
            ':restaurante_id' => $restauranteId,
            ':operacao_id' => $operacaoId,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function existeDuplicidadeDeDezMinutos(int $uhId, int $restauranteId, int $operacaoId, int $pax): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM acessos
            WHERE uh_id = :uh_id
              AND restaurante_id = :restaurante_id
              AND operacao_id = :operacao_id
              AND pax = :pax
              AND criado_em >= (NOW() - INTERVAL " . ControleSalaoConstants::DUPLICATE_ALERT_WINDOW_MINUTES . " MINUTE)
        ");
        $stmt->execute([
            ':uh_id' => $uhId,
            ':restaurante_id' => $restauranteId,
            ':operacao_id' => $operacaoId,
            ':pax' => $pax,
        ]);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0) > 0;
    }

    private function entradaEstaForaDoHorarioDaOperacao(?array $regraDeHorario): bool
    {
        if (function_exists('app_demo_mode_enabled') && app_demo_mode_enabled()) {
            return false;
        }
        if (!$regraDeHorario) {
            return false;
        }

        $tz = new DateTimeZone(date_default_timezone_get());
        $agora = new DateTime('now', $tz);
        $inicioOperacao = DateTime::createFromFormat('H:i:s', (string)$regraDeHorario['hora_inicio'], $tz);
        $fimOperacao = DateTime::createFromFormat('H:i:s', (string)$regraDeHorario['hora_fim'], $tz);
        if (!$inicioOperacao || !$fimOperacao) {
            return false;
        }

        $inicioOperacao->setDate((int)$agora->format('Y'), (int)$agora->format('m'), (int)$agora->format('d'));
        $fimOperacao->setDate((int)$agora->format('Y'), (int)$agora->format('m'), (int)$agora->format('d'));
        if ($fimOperacao < $inicioOperacao) {
            $fimOperacao->modify('+1 day');
            if ($agora < $inicioOperacao) {
                $agora->modify('+1 day');
            }
        }

        $tolerancia = (int)($regraDeHorario['tolerancia_min'] ?? 0);
        if ($tolerancia > 0) {
            $inicioOperacao->modify("-{$tolerancia} minutes");
            $fimOperacao->modify("+{$tolerancia} minutes");
        }

        return $agora < $inicioOperacao || $agora > $fimOperacao;
    }

    private function ehUhTecnica(string $uhNumero): bool
    {
        return in_array($uhNumero, ControleSalaoConstants::TECHNICAL_UHS, true);
    }

    private function multiploAcessoSql(string $alias): string
    {
        $uhTecnicaLista = implode("','", ControleSalaoConstants::TECHNICAL_UHS);
        $uhTecnicaSql = "(SELECT id FROM unidades_habitacionais WHERE numero IN ('{$uhTecnicaLista}'))";
        $janelaMultiploAcesso = ControleSalaoConstants::MULTIPLE_ACCESS_WINDOW_MINUTES;

        return "({$alias}.uh_id NOT IN {$uhTecnicaSql}) AND EXISTS (
            SELECT 1
            FROM acessos ax
            WHERE ax.uh_id = {$alias}.uh_id
              AND ax.uh_id NOT IN {$uhTecnicaSql}
              AND ax.operacao_id = {$alias}.operacao_id
              AND DATE(ax.criado_em) = DATE({$alias}.criado_em)
              AND ax.pax <> {$alias}.pax
              AND (ax.criado_em < {$alias}.criado_em OR (ax.criado_em = {$alias}.criado_em AND ax.id < {$alias}.id))
              AND TIMESTAMPDIFF(MINUTE, ax.criado_em, {$alias}.criado_em) > {$janelaMultiploAcesso}
        )";
    }

    private function statusOperacionalSql(string $alias): string
    {
        $multiploAcessoSql = $this->multiploAcessoSql($alias);
        return "
            CASE
                WHEN {$alias}.alerta_duplicidade = 1 THEN '" . ControleSalaoConstants::STATUS_DUPLICADO . "'
                WHEN {$alias}.fora_do_horario = 1 THEN '" . ControleSalaoConstants::STATUS_FORA_HORARIO . "'
                WHEN {$multiploAcessoSql} THEN '" . ControleSalaoConstants::STATUS_MULTIPLO_ACESSO . "'
                ELSE '" . ControleSalaoConstants::STATUS_OK . "'
            END
        ";
    }
}
