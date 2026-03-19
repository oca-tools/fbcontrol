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
            'flash' => get_flash(),
        ]);
    }
}
