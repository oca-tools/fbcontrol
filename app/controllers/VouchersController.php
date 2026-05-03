<?php
class VouchersController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'hostess']);

        $shiftModel = new ShiftModel();
        $shift = $shiftModel->getActiveByUser(Auth::user()['id']);

        $restaurantModel = new RestaurantModel();
        $operationModel = new OperationModel();
        $voucherModel = new VoucherModel();
        $filters = [
            'data' => date('Y-m-d'),
            'restaurante_id' => $shift['restaurante_id'] ?? '',
            'operacao_id' => $shift['operacao_id'] ?? '',
        ];
        $vouchers = $voucherModel->listByFilters($filters);

        $this->view('vouchers/index', [
            'shift' => $shift,
            'vouchers' => $vouchers,
            'restaurantes' => $restaurantModel->all(),
            'operacoes' => $operationModel->all(),
            'voucher_receive_limit_bytes' => upload_limit_bytes(10 * 1024 * 1024),
            'voucher_receive_limit_label' => format_bytes_ptbr(upload_limit_bytes(10 * 1024 * 1024)),
            'voucher_target_limit_bytes' => 5 * 1024 * 1024,
            'voucher_target_limit_label' => format_bytes_ptbr(5 * 1024 * 1024),
            'flash' => get_flash(),
        ]);
    }
}
