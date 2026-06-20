<?php
declare(strict_types=1);

final class UnitRepository extends RepositoryBase implements UnitRepositoryInterface
{
    /**
     * Recupera a UH pelo numero informado na reserva.
     */
    public function buscarUhPorNumero(string $uhNumero): ?array
    {
        return (new UnitModel())->findByNumero($uhNumero);
    }

    /**
     * Recupera a UH pelo identificador interno usado nas reservas existentes.
     */
    public function buscarUhPorId(int $uhId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM unidades_habitacionais WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $uhId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Consulta o limite de ocupacao da UH para validar o PAX reservado.
     */
    public function limitePaxDaUh(string $uhNumero): ?int
    {
        return (new UnitModel())->maxPaxForNumero($uhNumero);
    }
}
