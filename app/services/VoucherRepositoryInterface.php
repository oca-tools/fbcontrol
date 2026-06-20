<?php
declare(strict_types=1);

interface VoucherRepositoryInterface
{
    /**
     * Persiste um voucher com vinculo de restaurante, operacao e comprovante.
     */
    public function registrarVoucher(array $dadosVoucher, int $usuarioId): int;

    /**
     * Recupera um voucher para consulta operacional ou entrega segura do anexo.
     */
    public function buscarVoucher(int $voucherId): ?array;

    /**
     * Lista vouchers filtrados para conferencia de vendas, compensacoes e cortesias.
     */
    public function listarVouchers(array $filters, ?int $limit = null, int $offset = 0): array;
}
