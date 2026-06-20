<?php
declare(strict_types=1);

abstract class RepositoryBase
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    protected function intervaloDoDia(string $dataOperacao): array
    {
        $inicio = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', trim($dataOperacao) . ' 00:00:00');
        if (!$inicio) {
            $inicio = new DateTimeImmutable(date('Y-m-d 00:00:00'));
        }

        return [
            $inicio->format('Y-m-d H:i:s'),
            $inicio->modify('+1 day')->format('Y-m-d H:i:s'),
        ];
    }

    protected function intervaloEntreDias(string $dataInicio, string $dataFim): array
    {
        [$inicio] = $this->intervaloDoDia($dataInicio);
        [, $fim] = $this->intervaloDoDia($dataFim);
        return [$inicio, $fim];
    }

    protected function applyCreatedAtFilter(
        string &$where,
        array &$params,
        string $field,
        array $filters,
        string $prefix = 'data'
    ): void {
        $dataInicio = sanitize_date_param($filters['data_inicio'] ?? '');
        $dataFim = sanitize_date_param($filters['data_fim'] ?? '');
        $dataUnica = sanitize_date_param($filters['data'] ?? '');

        if ($dataInicio !== '' || $dataFim !== '') {
            if ($dataInicio === '') {
                $dataInicio = $dataFim;
            }
            if ($dataFim === '') {
                $dataFim = $dataInicio;
            }
            [$inicioDoPeriodo, $fimDoPeriodo] = $this->intervaloEntreDias($dataInicio, $dataFim);
            $where .= " AND {$field} >= :{$prefix}_inicio AND {$field} < :{$prefix}_fim";
            $params[":{$prefix}_inicio"] = $inicioDoPeriodo;
            $params[":{$prefix}_fim"] = $fimDoPeriodo;
            return;
        }

        if ($dataUnica !== '') {
            [$inicioDoDia, $fimDoDia] = $this->intervaloDoDia($dataUnica);
            $where .= " AND {$field} >= :{$prefix}_inicio AND {$field} < :{$prefix}_fim";
            $params[":{$prefix}_inicio"] = $inicioDoDia;
            $params[":{$prefix}_fim"] = $fimDoDia;
        }
    }

    protected function registrarAuditoria(
        string $acao,
        int $usuarioId,
        array $antes,
        array $depois,
        string $tabela,
        ?int $registroId = null
    ): void {
        $stmt = $this->db->prepare("
            INSERT INTO auditoria (tabela, registro_id, acao, usuario_id, dados_antes, dados_depois, criado_em)
            VALUES (:tabela, :registro_id, :acao, :usuario_id, :dados_antes, :dados_depois, NOW())
        ");
        $stmt->execute([
            ':tabela' => $tabela,
            ':registro_id' => $registroId,
            ':acao' => $acao,
            ':usuario_id' => $usuarioId,
            ':dados_antes' => json_encode(Model::sanitizeAuditPayload($antes), JSON_UNESCAPED_UNICODE),
            ':dados_depois' => json_encode(Model::sanitizeAuditPayload($depois), JSON_UNESCAPED_UNICODE),
        ]);
    }
}
