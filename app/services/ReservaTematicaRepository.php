<?php
declare(strict_types=1);

final class ReservaTematicaRepository extends RepositoryBase implements ReservaTematicaRepositoryInterface
{
    private ?bool $hasPaxRealColumnCache = null;
    private ?bool $hasTitularNomeColumnCache = null;
    private ?bool $hasGrupoIdColumnCache = null;
    private ?bool $hasPaxAdultoColumnCache = null;
    private ?bool $hasPaxChdColumnCache = null;
    private ?bool $hasQtdChdColumnCache = null;
    private ?bool $hasGrupoNomeColumnCache = null;
    private ?bool $hasGruposTableCache = null;
    private ?bool $hasChdTableCache = null;

    /**
     * Registra uma nova reserva temática, preservando campos opcionais conforme
     * a versão do schema disponível no ambiente.
     */
    public function criarReserva(array $dadosReserva, int $usuarioId): int
    {
        [$columns, $values, $params] = $this->montarCamposReserva($dadosReserva);

        $columns = array_merge($columns, [
            'observacao_reserva',
            'observacao_tags',
            'status',
            'excedente',
            'excedente_motivo',
            'excedente_autor_id',
            'excedente_em',
            'usuario_id',
            'criado_em',
        ]);
        $values = array_merge($values, [
            ':obs',
            ':tags',
            ':status',
            ':excedente',
            ':excedente_motivo',
            ':excedente_autor_id',
            ':excedente_em',
            ':usuario_id',
            'NOW()',
        ]);
        $params = array_merge($params, [
            ':obs' => $dadosReserva['observacao_reserva'] ?? null,
            ':tags' => $dadosReserva['observacao_tags'] ?? null,
            ':status' => $dadosReserva['status'] ?? ReservasTematicasConstants::STATUS_RESERVADA,
            ':excedente' => $dadosReserva['excedente'] ?? ReservasTematicasConstants::DEFAULT_ZERO,
            ':excedente_motivo' => $dadosReserva['excedente_motivo'] ?? null,
            ':excedente_autor_id' => $dadosReserva['excedente_autor_id'] ?? null,
            ':excedente_em' => $dadosReserva['excedente_em'] ?? null,
            ':usuario_id' => $usuarioId,
        ]);

        $stmt = $this->db->prepare("
            INSERT INTO reservas_tematicas
            (" . implode(', ', $columns) . ")
            VALUES (" . implode(', ', $values) . ")
        ");
        $stmt->execute($params);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Atualiza dados de reserva sem perder compatibilidade com colunas legadas.
     */
    public function atualizarReserva(int $reservaId, array $dadosReserva, int $usuarioId): void
    {
        [, , $params] = $this->montarCamposReserva($dadosReserva);
        $sets = [
            'restaurante_id = :restaurante_id',
            'data_reserva = :data_reserva',
            'turno_id = :turno_id',
            'uh_id = :uh_id',
            'pax = :pax',
        ];

        if ($this->hasGrupoIdColumn()) {
            $sets[] = 'grupo_id = :grupo_id';
        }
        if ($this->hasPaxAdultoColumn()) {
            $sets[] = 'pax_adulto = :pax_adulto';
        }
        if ($this->hasPaxChdColumn()) {
            $sets[] = 'pax_chd = :pax_chd';
        }
        if ($this->hasQtdChdColumn()) {
            $sets[] = 'qtd_chd = :qtd_chd';
        }
        if ($this->hasPaxRealColumn()) {
            $sets[] = 'pax_real = :pax_real';
        }
        if ($this->hasTitularNomeColumn()) {
            $sets[] = 'titular_nome = :titular_nome';
        }
        if ($this->hasGrupoNomeColumn()) {
            $sets[] = 'grupo_nome = :grupo_nome';
        }

        $sets = array_merge($sets, [
            'observacao_reserva = :obs',
            'observacao_tags = :tags',
            'status = :status',
            'excedente = :excedente',
            'excedente_motivo = :excedente_motivo',
            'excedente_autor_id = :excedente_autor_id',
            'excedente_em = :excedente_em',
            'atualizado_por = :atualizado_por',
            'atualizado_em = NOW()',
        ]);
        $params = array_merge($params, [
            ':obs' => $dadosReserva['observacao_reserva'] ?? null,
            ':tags' => $dadosReserva['observacao_tags'] ?? null,
            ':status' => $dadosReserva['status'] ?? ReservasTematicasConstants::STATUS_RESERVADA,
            ':excedente' => $dadosReserva['excedente'] ?? ReservasTematicasConstants::DEFAULT_ZERO,
            ':excedente_motivo' => $dadosReserva['excedente_motivo'] ?? null,
            ':excedente_autor_id' => $dadosReserva['excedente_autor_id'] ?? null,
            ':excedente_em' => $dadosReserva['excedente_em'] ?? null,
            ':atualizado_por' => $usuarioId,
            ':id' => $reservaId,
        ]);

        $stmt = $this->db->prepare("
            UPDATE reservas_tematicas
            SET " . implode(",\n                ", $sets) . "
            WHERE id = :id
        ");
        $stmt->execute($params);
    }

    /**
     * Cria o agrupador operacional para reservas em lote quando o schema suporta grupos.
     */
    public function criarGrupo(array $dadosGrupo, int $usuarioId): int
    {
        if (!$this->hasGruposTable()) {
            return ReservasTematicasConstants::DEFAULT_ZERO;
        }

        $stmt = $this->db->prepare("
            INSERT INTO reservas_tematicas_grupos
            (restaurante_id, data_reserva, turno_id, responsavel_nome, observacao_grupo, usuario_id, criado_em)
            VALUES (:restaurante_id, :data_reserva, :turno_id, :responsavel_nome, :observacao_grupo, :usuario_id, NOW())
        ");
        $stmt->execute([
            ':restaurante_id' => (int)($dadosGrupo['restaurante_id'] ?? 0),
            ':data_reserva' => (string)($dadosGrupo['data_reserva'] ?? ''),
            ':turno_id' => (int)($dadosGrupo['turno_id'] ?? 0),
            ':responsavel_nome' => $dadosGrupo['responsavel_nome'] ?? null,
            ':observacao_grupo' => $dadosGrupo['observacao_grupo'] ?? null,
            ':usuario_id' => $usuarioId,
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Substitui as idades CHD vinculadas a uma reserva.
     */
    public function substituirIdadesChd(int $reservaId, array $idades): void
    {
        if (!$this->hasChdTable()) {
            return;
        }

        $delete = $this->db->prepare("DELETE FROM reservas_tematicas_chd WHERE reserva_id = :reserva_id");
        $delete->execute([':reserva_id' => $reservaId]);
        if (empty($idades)) {
            return;
        }

        $hasLabel = $this->hasChdLabelColumn();
        $insert = $hasLabel
            ? $this->db->prepare("
                INSERT INTO reservas_tematicas_chd (reserva_id, idade, idade_label, criado_em)
                VALUES (:reserva_id, :idade, :idade_label, NOW())
            ")
            : $this->db->prepare("
                INSERT INTO reservas_tematicas_chd (reserva_id, idade, criado_em)
                VALUES (:reserva_id, :idade, NOW())
            ");
        foreach ($idades as $idade) {
            $idadeValor = is_array($idade) ? (int)($idade['idade'] ?? 0) : (int)$idade;
            $idadeLabel = is_array($idade) ? (string)($idade['label'] ?? ($idadeValor . 'y')) : ($idadeValor . 'y');
            $params = [
                ':reserva_id' => $reservaId,
                ':idade' => $idadeValor,
            ];
            if ($hasLabel) {
                $params[':idade_label'] = $idadeLabel;
            }
            $insert->execute($params);
        }
    }

    /**
     * Busca uma reserva por identificador para validação e auditoria operacional.
     */
    public function buscarReserva(int $reservaId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM reservas_tematicas WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $reservaId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Soma o PAX já reservado no turno, ignorando reservas canceladas.
     */
    public function somarPaxDoTurno(int $restauranteId, string $dataReserva, int $turnoId): int
    {
        $paxExpr = $this->hasPaxRealColumn() ? 'COALESCE(pax_real, pax)' : 'pax';
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM({$paxExpr}), 0) AS total_pax
            FROM reservas_tematicas
            WHERE restaurante_id = :restaurante_id
              AND data_reserva = :data_reserva
              AND turno_id = :turno_id
              AND status NOT IN ('" . ReservasTematicasConstants::STATUS_CANCELADA . "')
        ");
        $stmt->execute([
            ':restaurante_id' => $restauranteId,
            ':data_reserva' => $dataReserva,
            ':turno_id' => $turnoId,
        ]);
        $row = $stmt->fetch();
        return (int)($row['total_pax'] ?? 0);
    }

    /**
     * Localiza reserva ativa da mesma UH para impedir duplicidade no turno.
     */
    public function buscarReservaDuplicadaDaUh(int $uhId, string $dataReserva, int $turnoId, int $restauranteId): ?int
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM reservas_tematicas
            WHERE uh_id = :uh_id
              AND data_reserva = :data_reserva
              AND turno_id = :turno_id
              AND restaurante_id = :restaurante_id
              AND status <> '" . ReservasTematicasConstants::STATUS_CANCELADA . "'
            LIMIT 1
        ");
        $stmt->execute([
            ':uh_id' => $uhId,
            ':data_reserva' => $dataReserva,
            ':turno_id' => $turnoId,
            ':restaurante_id' => $restauranteId,
        ]);
        $row = $stmt->fetch();
        return $row ? (int)$row['id'] : null;
    }

    /**
     * Resolve a capacidade efetiva do turno, priorizando ajuste específico por data.
     */
    public function capacidadeDoTurno(int $restauranteId, string $dataReserva, int $turnoId): int
    {
        $stmt = $this->db->prepare("
            SELECT capacidade
            FROM reservas_tematicas_capacidades_datas
            WHERE restaurante_id = :restaurante_id
              AND data_reserva = :data_reserva
              AND turno_id = :turno_id
            LIMIT 1
        ");
        $stmt->execute([
            ':restaurante_id' => $restauranteId,
            ':data_reserva' => $dataReserva,
            ':turno_id' => $turnoId,
        ]);
        $row = $stmt->fetch();
        if ($row) {
            return (int)($row['capacidade'] ?? 0);
        }

        $stmtDefault = $this->db->prepare("
            SELECT capacidade
            FROM reservas_tematicas_config_turnos
            WHERE restaurante_id = :restaurante_id
              AND turno_id = :turno_id
            LIMIT 1
        ");
        $stmtDefault->execute([
            ':restaurante_id' => $restauranteId,
            ':turno_id' => $turnoId,
        ]);
        $defaultRow = $stmtDefault->fetch();
        return (int)($defaultRow['capacidade'] ?? 0);
    }

    /**
     * Verifica bloqueios de data ou semana para impedir novas reservas no restaurante.
     */
    public function restauranteFechadoNaData(int $restauranteId, string $dataReserva): bool
    {
        $stmt = $this->db->prepare("
            SELECT modo
            FROM reservas_tematicas_bloqueios_datas
            WHERE restaurante_id = :restaurante_id
              AND data_reserva = :data_reserva
              AND ativo = 1
            LIMIT 1
        ");
        $stmt->execute([
            ':restaurante_id' => $restauranteId,
            ':data_reserva' => $dataReserva,
        ]);
        $override = $stmt->fetch();
        if ($override) {
            return (string)($override['modo'] ?? 'fechado') !== 'aberto';
        }

        $diaSemana = (int)(new DateTimeImmutable($dataReserva))->format('w');
        $stmtSemanal = $this->db->prepare("
            SELECT id
            FROM reservas_tematicas_bloqueios_semanais
            WHERE restaurante_id = :restaurante_id
              AND dia_semana = :dia_semana
              AND ativo = 1
            LIMIT 1
        ");
        $stmtSemanal->execute([
            ':restaurante_id' => $restauranteId,
            ':dia_semana' => $diaSemana,
        ]);
        return (bool)$stmtSemanal->fetch();
    }

    /**
     * Confirma se a operação do turno já foi encerrada pela equipe.
     */
    public function turnoOperacionalFechado(int $restauranteId, string $dataReserva, int $turnoId): bool
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM reservas_tematicas_fechamentos
            WHERE restaurante_id = :restaurante_id
              AND data_reserva = :data_reserva
              AND turno_id = :turno_id
            LIMIT 1
        ");
        $stmt->execute([
            ':restaurante_id' => $restauranteId,
            ':data_reserva' => $dataReserva,
            ':turno_id' => $turnoId,
        ]);
        return (bool)$stmt->fetch();
    }

    /**
     * Encerra operacionalmente um turno temático para travar alterações comuns.
     */
    public function fecharTurnoOperacional(int $restauranteId, string $dataReserva, int $turnoId, int $usuarioId): void
    {
        if ($this->turnoOperacionalFechado($restauranteId, $dataReserva, $turnoId)) {
            return;
        }

        $stmt = $this->db->prepare("
            INSERT INTO reservas_tematicas_fechamentos
            (restaurante_id, data_reserva, turno_id, fechado_em, usuario_id)
            VALUES (:restaurante_id, :data_reserva, :turno_id, NOW(), :usuario_id)
        ");
        $stmt->execute([
            ':restaurante_id' => $restauranteId,
            ':data_reserva' => $dataReserva,
            ':turno_id' => $turnoId,
            ':usuario_id' => $usuarioId,
        ]);
    }

    /**
     * Atualiza o status de presença da reserva e registra PAX real quando disponível.
     */
    public function atualizarStatusOperacao(int $reservaId, string $status, ?string $observacao, int $usuarioId, ?int $paxReal = null): void
    {
        if ($this->hasPaxRealColumn()) {
            $stmt = $this->db->prepare("
                UPDATE reservas_tematicas
                SET status = :status,
                    pax_real = CASE WHEN :pax_real_check IS NULL THEN pax_real ELSE :pax_real_value END,
                    observacao_operacao = :observacao_operacao,
                    atualizado_por = :atualizado_por,
                    atualizado_em = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                ':status' => $status,
                ':pax_real_check' => $paxReal,
                ':pax_real_value' => $paxReal,
                ':observacao_operacao' => $observacao,
                ':atualizado_por' => $usuarioId,
                ':id' => $reservaId,
            ]);
            return;
        }

        $stmt = $this->db->prepare("
            UPDATE reservas_tematicas
            SET status = :status,
                pax = CASE WHEN :pax_check IS NULL THEN pax ELSE :pax_value END,
                observacao_operacao = :observacao_operacao,
                atualizado_por = :atualizado_por,
                atualizado_em = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':status' => $status,
            ':pax_check' => $paxReal,
            ':pax_value' => $paxReal,
            ':observacao_operacao' => $observacao,
            ':atualizado_por' => $usuarioId,
            ':id' => $reservaId,
        ]);
    }

    /**
     * Move ou ajusta a reserva durante a operação, mantendo restaurante, turno,
     * status e observação coerentes com a conferência do salão.
     */
    public function atualizarDetalhesOperacao(int $reservaId, array $dadosOperacao, int $usuarioId): void
    {
        $set = "
            restaurante_id = :restaurante_id,
            turno_id = :turno_id,
            status = :status,
            observacao_operacao = :observacao_operacao,
            atualizado_por = :atualizado_por,
            atualizado_em = NOW()
        ";
        $params = [
            ':restaurante_id' => (int)$dadosOperacao['restaurante_id'],
            ':turno_id' => (int)$dadosOperacao['turno_id'],
            ':status' => (string)$dadosOperacao['status'],
            ':observacao_operacao' => $dadosOperacao['observacao_operacao'] ?? null,
            ':atualizado_por' => $usuarioId,
            ':id' => $reservaId,
        ];

        if ($this->hasPaxRealColumn()) {
            $set .= ",
                pax_real = CASE
                    WHEN :pax_check IS NULL THEN pax_real
                    ELSE :pax_value
                END
            ";
            $params[':pax_check'] = $dadosOperacao['pax_real'] ?? null;
            $params[':pax_value'] = $dadosOperacao['pax_real'] ?? null;
        }

        $stmt = $this->db->prepare("UPDATE reservas_tematicas SET {$set} WHERE id = :id");
        $stmt->execute($params);
    }

    /**
     * Registra trilha de auditoria das mudanças de reserva temática.
     */
    public function registrarLog(int $reservaId, string $acao, int $usuarioId, array $antes = [], array $depois = [], ?string $justificativa = null): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO reservas_tematicas_logs
            (reserva_id, acao, usuario_id, dados_antes, dados_depois, justificativa, criado_em)
            VALUES (:reserva_id, :acao, :usuario_id, :antes, :depois, :justificativa, NOW())
        ");
        $stmt->execute([
            ':reserva_id' => $reservaId,
            ':acao' => $acao,
            ':usuario_id' => $usuarioId,
            ':antes' => !empty($antes) ? json_encode($antes, JSON_UNESCAPED_UNICODE) : null,
            ':depois' => !empty($depois) ? json_encode($depois, JSON_UNESCAPED_UNICODE) : null,
            ':justificativa' => $justificativa,
        ]);
    }

    /**
     * Lista reservas que ultrapassaram a tolerância configurada e ainda seguem reservadas.
     */
    public function listarCandidatasAutoNoShow(string $agora, ?string $dataReserva = null, ?int $restauranteId = null): array
    {
        $where = "
            WHERE rsv.status = '" . ReservasTematicasConstants::STATUS_RESERVADA . "'
              AND COALESCE(cfg.auto_cancel_no_show_min, 0) > 0
              AND DATE_ADD(TIMESTAMP(rsv.data_reserva, t.hora), INTERVAL cfg.auto_cancel_no_show_min MINUTE) <= :agora
        ";
        $params = [':agora' => $agora];

        if ($dataReserva !== null && $dataReserva !== '') {
            $where .= " AND rsv.data_reserva = :data_reserva";
            $params[':data_reserva'] = $dataReserva;
        }
        if ($restauranteId !== null && $restauranteId > 0) {
            $where .= " AND rsv.restaurante_id = :restaurante_id";
            $params[':restaurante_id'] = $restauranteId;
        }

        $stmt = $this->db->prepare("
            SELECT rsv.id
            FROM reservas_tematicas rsv
            JOIN reservas_tematicas_turnos t ON t.id = rsv.turno_id
            JOIN reservas_tematicas_config cfg ON cfg.restaurante_id = rsv.restaurante_id
            {$where}
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Executa uma operação de reserva em transação única.
     */
    public function executarTransacao(callable $operacao): void
    {
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }
        try {
            $operacao();
            if ($ownsTransaction) {
                $this->db->commit();
            }
        } catch (Throwable $e) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function montarCamposReserva(array $dadosReserva): array
    {
        $columns = ['restaurante_id', 'data_reserva', 'turno_id', 'uh_id', 'pax'];
        $values = [':restaurante_id', ':data_reserva', ':turno_id', ':uh_id', ':pax'];
        $params = [
            ':restaurante_id' => (int)$dadosReserva['restaurante_id'],
            ':data_reserva' => (string)$dadosReserva['data_reserva'],
            ':turno_id' => (int)$dadosReserva['turno_id'],
            ':uh_id' => (int)$dadosReserva['uh_id'],
            ':pax' => (int)$dadosReserva['pax'],
        ];

        if ($this->hasGrupoIdColumn()) {
            $columns[] = 'grupo_id';
            $values[] = ':grupo_id';
            $params[':grupo_id'] = $dadosReserva['grupo_id'] ?? null;
        }
        if ($this->hasPaxAdultoColumn()) {
            $columns[] = 'pax_adulto';
            $values[] = ':pax_adulto';
            $params[':pax_adulto'] = (int)($dadosReserva['pax_adulto'] ?? $dadosReserva['pax'] ?? ReservasTematicasConstants::DEFAULT_ZERO);
        }
        if ($this->hasPaxChdColumn()) {
            $columns[] = 'pax_chd';
            $values[] = ':pax_chd';
            $params[':pax_chd'] = (int)($dadosReserva['pax_chd'] ?? ReservasTematicasConstants::DEFAULT_ZERO);
        }
        if ($this->hasQtdChdColumn()) {
            $columns[] = 'qtd_chd';
            $values[] = ':qtd_chd';
            $params[':qtd_chd'] = (int)($dadosReserva['qtd_chd'] ?? ($dadosReserva['pax_chd'] ?? ReservasTematicasConstants::DEFAULT_ZERO));
        }
        if ($this->hasPaxRealColumn()) {
            $columns[] = 'pax_real';
            $values[] = ':pax_real';
            $params[':pax_real'] = $dadosReserva['pax_real'] ?? null;
        }
        if ($this->hasTitularNomeColumn()) {
            $columns[] = 'titular_nome';
            $values[] = ':titular_nome';
            $params[':titular_nome'] = $dadosReserva['titular_nome'] ?? null;
        }
        if ($this->hasGrupoNomeColumn()) {
            $columns[] = 'grupo_nome';
            $values[] = ':grupo_nome';
            $params[':grupo_nome'] = $dadosReserva['grupo_nome'] ?? null;
        }

        return [$columns, $values, $params];
    }

    private function hasColumn(string $column, ?bool &$cache): bool
    {
        if ($cache !== null) {
            return $cache;
        }
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM reservas_tematicas LIKE " . $this->db->quote($column));
            $cache = (bool)$stmt->fetch();
        } catch (Throwable $e) {
            $cache = false;
        }
        return $cache;
    }

    private function hasTable(string $table, ?bool &$cache): bool
    {
        if ($cache !== null) {
            return $cache;
        }
        try {
            $stmt = $this->db->query("SHOW TABLES LIKE " . $this->db->quote($table));
            $cache = (bool)$stmt->fetch();
        } catch (Throwable $e) {
            $cache = false;
        }
        return $cache;
    }

    private function hasPaxRealColumn(): bool
    {
        return $this->hasColumn('pax_real', $this->hasPaxRealColumnCache);
    }

    private function hasTitularNomeColumn(): bool
    {
        return $this->hasColumn('titular_nome', $this->hasTitularNomeColumnCache);
    }

    private function hasGrupoIdColumn(): bool
    {
        return $this->hasColumn('grupo_id', $this->hasGrupoIdColumnCache);
    }

    private function hasPaxAdultoColumn(): bool
    {
        return $this->hasColumn('pax_adulto', $this->hasPaxAdultoColumnCache);
    }

    private function hasPaxChdColumn(): bool
    {
        return $this->hasColumn('pax_chd', $this->hasPaxChdColumnCache);
    }

    private function hasQtdChdColumn(): bool
    {
        return $this->hasColumn('qtd_chd', $this->hasQtdChdColumnCache);
    }

    private function hasGrupoNomeColumn(): bool
    {
        return $this->hasColumn('grupo_nome', $this->hasGrupoNomeColumnCache);
    }

    private function hasGruposTable(): bool
    {
        return $this->hasTable('reservas_tematicas_grupos', $this->hasGruposTableCache);
    }

    private function hasChdTable(): bool
    {
        return $this->hasTable('reservas_tematicas_chd', $this->hasChdTableCache);
    }

    private ?bool $hasChdLabelColumnCache = null;

    private function hasChdLabelColumn(): bool
    {
        if ($this->hasChdLabelColumnCache !== null) {
            return $this->hasChdLabelColumnCache;
        }

        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM reservas_tematicas_chd LIKE 'idade_label'");
            $this->hasChdLabelColumnCache = (bool)$stmt->fetch();
        } catch (Throwable $e) {
            $this->hasChdLabelColumnCache = false;
        }

        return $this->hasChdLabelColumnCache;
    }
}
