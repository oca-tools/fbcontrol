<?php
declare(strict_types=1);

interface UnitRepositoryInterface
{
    /**
     * Recupera a UH pelo numero informado na reserva.
     */
    public function buscarUhPorNumero(string $uhNumero): ?array;

    /**
     * Recupera a UH pelo identificador interno usado nas reservas existentes.
     */
    public function buscarUhPorId(int $uhId): ?array;

    /**
     * Consulta o limite de ocupacao da UH para validar o PAX reservado.
     */
    public function limitePaxDaUh(string $uhNumero): ?int;
}
