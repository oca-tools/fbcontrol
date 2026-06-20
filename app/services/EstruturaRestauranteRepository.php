<?php
declare(strict_types=1);

final class EstruturaRestauranteRepository extends RepositoryBase implements EstruturaRestauranteRepositoryInterface
{
    /**
     * Lista restaurantes configurados para as rotinas de A&B.
     */
    public function listarRestaurantes(): array
    {
        return $this->db->query("SELECT * FROM restaurantes ORDER BY nome")->fetchAll();
    }

    /**
     * Persiste um novo restaurante operacional.
     */
    public function criarRestaurante(array $dadosRestaurante, int $usuarioId): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO restaurantes (nome, tipo, seleciona_porta_no_turno, exige_pax, ativo, criado_em)
            VALUES (:nome, :tipo, :seleciona_porta_no_turno, :exige_pax, :ativo, NOW())
        ");
        $stmt->execute([
            ':nome' => $dadosRestaurante['nome'],
            ':tipo' => $dadosRestaurante['tipo'] ?? GestaoRestaurantesConstants::RESTAURANT_TYPE_BUFFET,
            ':seleciona_porta_no_turno' => $dadosRestaurante['seleciona_porta_no_turno'] ?? GestaoRestaurantesConstants::STATUS_INACTIVE,
            ':exige_pax' => $dadosRestaurante['exige_pax'] ?? GestaoRestaurantesConstants::STATUS_ACTIVE,
            ':ativo' => $dadosRestaurante['ativo'] ?? GestaoRestaurantesConstants::STATUS_ACTIVE,
        ]);

        $restauranteId = (int)$this->db->lastInsertId();
        $this->registrarAuditoria(GestaoRestaurantesConstants::AUDIT_CREATE, $usuarioId, [], array_merge($dadosRestaurante, ['id' => $restauranteId]), GestaoRestaurantesConstants::ENTITY_RESTAURANTES, $restauranteId);
        return $restauranteId;
    }

    /**
     * Atualiza regras e status de um restaurante operacional.
     */
    public function atualizarRestaurante(int $restauranteId, array $dadosRestaurante, int $usuarioId): void
    {
        $antes = $this->buscarRestaurante($restauranteId) ?? [];
        $stmt = $this->db->prepare("
            UPDATE restaurantes
            SET nome = :nome,
                tipo = :tipo,
                seleciona_porta_no_turno = :seleciona_porta_no_turno,
                exige_pax = :exige_pax,
                ativo = :ativo
            WHERE id = :id
        ");
        $stmt->execute([
            ':nome' => $dadosRestaurante['nome'],
            ':tipo' => $dadosRestaurante['tipo'] ?? GestaoRestaurantesConstants::RESTAURANT_TYPE_BUFFET,
            ':seleciona_porta_no_turno' => $dadosRestaurante['seleciona_porta_no_turno'] ?? GestaoRestaurantesConstants::STATUS_INACTIVE,
            ':exige_pax' => $dadosRestaurante['exige_pax'] ?? GestaoRestaurantesConstants::STATUS_ACTIVE,
            ':ativo' => $dadosRestaurante['ativo'] ?? GestaoRestaurantesConstants::STATUS_ACTIVE,
            ':id' => $restauranteId,
        ]);

        $depois = $this->buscarRestaurante($restauranteId) ?? [];
        $this->registrarAuditoria(GestaoRestaurantesConstants::AUDIT_UPDATE, $usuarioId, $antes, $depois, GestaoRestaurantesConstants::ENTITY_RESTAURANTES, $restauranteId);
    }

    /**
     * Lista as operacoes disponiveis para vinculo aos restaurantes.
     */
    public function listarOperacoes(): array
    {
        $rows = $this->db->query("SELECT * FROM operacoes ORDER BY nome, id")->fetchAll();
        return $this->manterOperacoesComNomeUnico($rows);
    }

    /**
     * Persiste uma nova operacao de A&B.
     */
    public function criarOperacao(array $dadosOperacao, int $usuarioId): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO operacoes (nome, ativo, criado_em)
            VALUES (:nome, :ativo, NOW())
        ");
        $stmt->execute([
            ':nome' => $dadosOperacao['nome'],
            ':ativo' => $dadosOperacao['ativo'] ?? GestaoRestaurantesConstants::STATUS_ACTIVE,
        ]);

        $operacaoId = (int)$this->db->lastInsertId();
        $this->registrarAuditoria(GestaoRestaurantesConstants::AUDIT_CREATE, $usuarioId, [], array_merge($dadosOperacao, ['id' => $operacaoId]), GestaoRestaurantesConstants::ENTITY_OPERACOES, $operacaoId);
        return $operacaoId;
    }

    /**
     * Atualiza status e nome de uma operacao de A&B.
     */
    public function atualizarOperacao(int $operacaoId, array $dadosOperacao, int $usuarioId): void
    {
        $antes = $this->buscarOperacao($operacaoId) ?? [];
        $stmt = $this->db->prepare("
            UPDATE operacoes
            SET nome = :nome,
                ativo = :ativo
            WHERE id = :id
        ");
        $stmt->execute([
            ':nome' => $dadosOperacao['nome'],
            ':ativo' => $dadosOperacao['ativo'] ?? GestaoRestaurantesConstants::STATUS_ACTIVE,
            ':id' => $operacaoId,
        ]);

        $depois = $this->buscarOperacao($operacaoId) ?? [];
        $this->registrarAuditoria(GestaoRestaurantesConstants::AUDIT_UPDATE, $usuarioId, $antes, $depois, GestaoRestaurantesConstants::ENTITY_OPERACOES, $operacaoId);
    }

    /**
     * Lista portas de acesso configuradas por restaurante.
     */
    public function listarPontosDeAcesso(): array
    {
        return $this->db->query("
            SELECT p.*, r.nome AS restaurante
            FROM portas p
            JOIN restaurantes r ON r.id = p.restaurante_id
            ORDER BY r.nome, p.nome
        ")->fetchAll();
    }

    /**
     * Persiste uma porta usada no registro de entrada de hospedes.
     */
    public function criarPontoDeAcesso(array $dadosPontoDeAcesso, int $usuarioId): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO portas (restaurante_id, nome, ativo, criado_em)
            VALUES (:restaurante_id, :nome, :ativo, NOW())
        ");
        $stmt->execute([
            ':restaurante_id' => $dadosPontoDeAcesso['restaurante_id'],
            ':nome' => $dadosPontoDeAcesso['nome'],
            ':ativo' => $dadosPontoDeAcesso['ativo'] ?? GestaoRestaurantesConstants::STATUS_ACTIVE,
        ]);

        $pontoDeAcessoId = (int)$this->db->lastInsertId();
        $this->registrarAuditoria(GestaoRestaurantesConstants::AUDIT_CREATE, $usuarioId, [], array_merge($dadosPontoDeAcesso, ['id' => $pontoDeAcessoId]), GestaoRestaurantesConstants::ENTITY_PORTAS, $pontoDeAcessoId);
        return $pontoDeAcessoId;
    }

    /**
     * Verifica se o restaurante ja possui porta equivalente cadastrada.
     */
    public function pontoDeAcessoDuplicado(int $restauranteId, string $nomePontoDeAcesso, int $ignorarPontoDeAcessoId = 0): bool
    {
        $sql = "
            SELECT id
            FROM portas
            WHERE restaurante_id = :restaurante_id
              AND LOWER(TRIM(nome)) = LOWER(TRIM(:nome))
        ";
        $params = [
            ':restaurante_id' => $restauranteId,
            ':nome' => $nomePontoDeAcesso,
        ];

        if ($ignorarPontoDeAcessoId > 0) {
            $sql .= " AND id <> :id";
            $params[':id'] = $ignorarPontoDeAcessoId;
        }

        $sql .= " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    /**
     * Atualiza uma porta de acesso e seu vinculo operacional.
     */
    public function atualizarPontoDeAcesso(int $pontoDeAcessoId, array $dadosPontoDeAcesso, int $usuarioId): void
    {
        $antes = $this->buscarPontoDeAcesso($pontoDeAcessoId) ?? [];
        $stmt = $this->db->prepare("
            UPDATE portas
            SET restaurante_id = :restaurante_id,
                nome = :nome,
                ativo = :ativo
            WHERE id = :id
        ");
        $stmt->execute([
            ':restaurante_id' => $dadosPontoDeAcesso['restaurante_id'],
            ':nome' => $dadosPontoDeAcesso['nome'],
            ':ativo' => $dadosPontoDeAcesso['ativo'] ?? GestaoRestaurantesConstants::STATUS_ACTIVE,
            ':id' => $pontoDeAcessoId,
        ]);

        $depois = $this->buscarPontoDeAcesso($pontoDeAcessoId) ?? [];
        $this->registrarAuditoria(GestaoRestaurantesConstants::AUDIT_UPDATE, $usuarioId, $antes, $depois, GestaoRestaurantesConstants::ENTITY_PORTAS, $pontoDeAcessoId);
    }

    /**
     * Lista a grade de horarios por restaurante e operacao.
     */
    public function listarHorariosDaOperacao(): array
    {
        return $this->db->query("
            SELECT ro.*, r.nome AS restaurante, o.nome AS operacao
            FROM restaurante_operacoes ro
            JOIN restaurantes r ON r.id = ro.restaurante_id
            JOIN operacoes o ON o.id = ro.operacao_id
            ORDER BY r.nome, o.nome
        ")->fetchAll();
    }

    /**
     * Persiste o horario em que uma operacao estara ativa para atendimento.
     */
    public function criarHorarioDaOperacao(array $dadosHorario, int $usuarioId): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO restaurante_operacoes
            (restaurante_id, operacao_id, hora_inicio, hora_fim, tolerancia_min, ativo)
            VALUES (:restaurante_id, :operacao_id, :hora_inicio, :hora_fim, :tolerancia_min, :ativo)
        ");
        $stmt->execute([
            ':restaurante_id' => $dadosHorario['restaurante_id'],
            ':operacao_id' => $dadosHorario['operacao_id'],
            ':hora_inicio' => $dadosHorario['hora_inicio'],
            ':hora_fim' => $dadosHorario['hora_fim'],
            ':tolerancia_min' => $dadosHorario['tolerancia_min'] ?? GestaoRestaurantesConstants::DEFAULT_TOLERANCE_MINUTES,
            ':ativo' => $dadosHorario['ativo'] ?? GestaoRestaurantesConstants::STATUS_ACTIVE,
        ]);

        $horarioId = (int)$this->db->lastInsertId();
        $this->registrarAuditoria(GestaoRestaurantesConstants::AUDIT_CREATE, $usuarioId, [], array_merge($dadosHorario, ['id' => $horarioId]), GestaoRestaurantesConstants::ENTITY_RESTAURANTE_OPERACOES, $horarioId);
        return $horarioId;
    }

    /**
     * Atualiza horario, tolerancia e status da operacao de restaurante.
     */
    public function atualizarHorarioDaOperacao(int $horarioId, array $dadosHorario, int $usuarioId): void
    {
        $antes = $this->buscarHorarioDaOperacao($horarioId) ?? [];
        $stmt = $this->db->prepare("
            UPDATE restaurante_operacoes
            SET restaurante_id = :restaurante_id,
                operacao_id = :operacao_id,
                hora_inicio = :hora_inicio,
                hora_fim = :hora_fim,
                tolerancia_min = :tolerancia_min,
                ativo = :ativo
            WHERE id = :id
        ");
        $stmt->execute([
            ':restaurante_id' => $dadosHorario['restaurante_id'],
            ':operacao_id' => $dadosHorario['operacao_id'],
            ':hora_inicio' => $dadosHorario['hora_inicio'],
            ':hora_fim' => $dadosHorario['hora_fim'],
            ':tolerancia_min' => $dadosHorario['tolerancia_min'] ?? GestaoRestaurantesConstants::DEFAULT_TOLERANCE_MINUTES,
            ':ativo' => $dadosHorario['ativo'] ?? GestaoRestaurantesConstants::STATUS_ACTIVE,
            ':id' => $horarioId,
        ]);

        $depois = $this->buscarHorarioDaOperacao($horarioId) ?? [];
        $this->registrarAuditoria(GestaoRestaurantesConstants::AUDIT_UPDATE, $usuarioId, $antes, $depois, GestaoRestaurantesConstants::ENTITY_RESTAURANTE_OPERACOES, $horarioId);
    }

    private function buscarRestaurante(int $restauranteId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM restaurantes WHERE id = :id");
        $stmt->execute([':id' => $restauranteId]);
        $restaurante = $stmt->fetch();
        return $restaurante ?: null;
    }

    private function buscarOperacao(int $operacaoId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM operacoes WHERE id = :id");
        $stmt->execute([':id' => $operacaoId]);
        $operacao = $stmt->fetch();
        return $operacao ?: null;
    }

    private function buscarPontoDeAcesso(int $pontoDeAcessoId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM portas WHERE id = :id");
        $stmt->execute([':id' => $pontoDeAcessoId]);
        $pontoDeAcesso = $stmt->fetch();
        return $pontoDeAcesso ?: null;
    }

    private function buscarHorarioDaOperacao(int $horarioId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM restaurante_operacoes WHERE id = :id");
        $stmt->execute([':id' => $horarioId]);
        $horario = $stmt->fetch();
        return $horario ?: null;
    }

    private function manterOperacoesComNomeUnico(array $operacoes): array
    {
        $operacoesPorNomeNormalizado = [];
        foreach ($operacoes as $operacao) {
            $nomeOperacao = trim((string)($operacao['nome'] ?? ''));
            if ($nomeOperacao === '') {
                continue;
            }

            $nomeNormalizado = mb_strtolower(normalize_mojibake($nomeOperacao), 'UTF-8');
            $nomeAscii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nomeNormalizado);
            $chaveOperacao = preg_replace('/[^a-z0-9]/', '', (string)$nomeAscii);
            if ($chaveOperacao === '') {
                $chaveOperacao = $nomeNormalizado;
            }

            if (!isset($operacoesPorNomeNormalizado[$chaveOperacao])) {
                $operacao['nome'] = normalize_mojibake($nomeOperacao);
                $operacoesPorNomeNormalizado[$chaveOperacao] = $operacao;
            }
        }

        return array_values($operacoesPorNomeNormalizado);
    }
}
