<?php
class HostessController extends Controller
{
    private const PROFILE_PHOTO_MAX_BYTES = 2097152;
    private const PROFILE_PHOTO_MAX_PIXELS = 30000000;
    private const PROFILE_PHOTO_MAX_SIDE = 8000;

    public function turnos(): void
    {
        $this->perfil();
    }

    /**
     * Espaço pessoal da hostess: conta, turnos e todas as reservas criadas.
     */
    public function perfil(): void
    {
        $this->requireAuth();
        Auth::requireRole(['hostess']);

        $user = Auth::user() ?? [];
        $shiftModel = new ShiftModel();
        $turnos = $shiftModel->listByUser((int)$user['id'], 50);
        usort($turnos, fn($a, $b) => strcmp($b['inicio_em'], $a['inicio_em']));

        $completed = $shiftModel->countCompletedByUser((int)$user['id']);
        $level = $this->levelFor($completed);
        $quickPeriod = (int)($_GET['periodo'] ?? 0);
        $quickPeriod = in_array($quickPeriod, [7, 15, 30], true) ? $quickPeriod : 0;
        $date = sanitize_date_param($_GET['data'] ?? '', '');
        $status = trim((string)($_GET['status'] ?? ''));
        $search = trim((string)($_GET['q'] ?? ''));

        $reservationFilters = [
            'usuario_id' => (int)$user['id'],
            'status' => $status,
            'q' => mb_substr($search, 0, 100, 'UTF-8'),
        ];
        if ($quickPeriod > 0) {
            $reservationFilters['data_inicio'] = date('Y-m-d', strtotime('-' . ($quickPeriod - 1) . ' days'));
            $reservationFilters['data_fim'] = date('Y-m-d');
            $date = '';
        } else {
            $date = $date !== '' ? $date : date('Y-m-d');
            $reservationFilters['data'] = $date;
        }

        $perPage = 12;
        $totalReservations = (new ReservaTematicaModel())->countByFilters($reservationFilters);
        $totalPages = max(1, (int)ceil($totalReservations / $perPage));
        $page = max(1, min($totalPages, (int)($_GET['page'] ?? 1)));
        $reservations = (new ReservaTematicaModel())->listByFilters($reservationFilters, $perPage, ($page - 1) * $perPage);

        $this->view('hostess/turnos', [
            'turnos' => $turnos,
            'completed' => $completed,
            'level' => $level,
            'reservas' => $reservations,
            'reservas_total' => $totalReservations,
            'reservas_page' => $page,
            'reservas_total_pages' => $totalPages,
            'reservation_filters' => [
                'periodo' => $quickPeriod,
                'data' => $date,
                'status' => $status,
                'q' => $search,
            ],
            'flash' => get_flash(),
        ]);
    }

    /** Troca a senha da própria hostess sem criar ambiguidade no e-mail compartilhado. */
    public function senha(): void
    {
        $this->requireAuth();
        Auth::requireRole(['hostess']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=hostess/perfil');
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Sua sessão expirou. Atualize a página e tente novamente.');
            $this->redirect('/?r=hostess/perfil');
        }

        $newPassword = (string)($_POST['nova_senha'] ?? '');
        $confirmation = (string)($_POST['confirmacao_senha'] ?? '');
        if ($newPassword !== $confirmation) {
            set_flash('danger', 'A confirmação da nova senha não confere.');
            $this->redirect('/?r=hostess/perfil');
        }

        $result = (new UserModel())->changeOwnPassword(
            (int)(Auth::user()['id'] ?? 0),
            (string)($_POST['senha_atual'] ?? ''),
            $newPassword
        );
        set_flash($result['ok'] ? 'success' : 'danger', $result['message']);
        $this->redirect('/?r=hostess/perfil');
    }

    public function foto(): void
    {
        $this->requireAuth();
        Auth::requireRole(['hostess']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=hostess/perfil');
        }

        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=hostess/perfil');
        }

        if (empty($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            set_flash('danger', 'Falha ao enviar a foto.');
            $this->redirect('/?r=hostess/perfil');
        }

        $file = $_FILES['foto'];
        if (!is_uploaded_file($file['tmp_name'])) {
            set_flash('danger', 'Upload inválido.');
            $this->redirect('/?r=hostess/perfil');
        }
        $receiveLimit = upload_limit_bytes(self::PROFILE_PHOTO_MAX_BYTES);
        if ((int)($file['size'] ?? 0) > $receiveLimit) {
            set_flash('danger', 'Arquivo muito grande. Máximo ' . format_bytes_ptbr($receiveLimit) . '.');
            $this->redirect('/?r=hostess/perfil');
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            set_flash('danger', 'Formato inválido. Use JPG, PNG ou WEBP.');
            $this->redirect('/?r=hostess/perfil');
        }

        if (!class_exists('finfo')) {
            set_flash('danger', 'Não foi possível validar a imagem no servidor. Avise o suporte.');
            $this->redirect('/?r=hostess/perfil');
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: '';
        $allowedMimeByExt = [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'webp' => ['image/webp'],
        ];
        if (!in_array($mime, $allowedMimeByExt[$ext] ?? [], true)) {
            set_flash('danger', 'Conteudo de arquivo inválido para a imagem enviada.');
            $this->redirect('/?r=hostess/perfil');
        }

        $imageInfo = @getimagesize($file['tmp_name']);
        $width = (int)($imageInfo[0] ?? 0);
        $height = (int)($imageInfo[1] ?? 0);
        if ($width <= 0 || $height <= 0) {
            set_flash('danger', 'Não foi possível validar as dimensões da imagem.');
            $this->redirect('/?r=hostess/perfil');
        }
        if ($width > self::PROFILE_PHOTO_MAX_SIDE || $height > self::PROFILE_PHOTO_MAX_SIDE || ($width * $height) > self::PROFILE_PHOTO_MAX_PIXELS) {
            set_flash('danger', 'Imagem com dimensões muito grandes. Envie uma foto menor.');
            $this->redirect('/?r=hostess/perfil');
        }

        $uploadDir = __DIR__ . '/../../public/uploads/profiles';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            set_flash('danger', 'Pasta de fotos indisponível. Avise o suporte.');
            $this->redirect('/?r=hostess/perfil');
        }
        if (!is_writable($uploadDir)) {
            set_flash('danger', 'Pasta de fotos sem permissão de gravação. Avise o suporte.');
            $this->redirect('/?r=hostess/perfil');
        }

        $userId = Auth::user()['id'];
        foreach (glob($uploadDir . '/user_' . $userId . '_*.*') ?: [] as $oldFile) {
            if (is_file($oldFile)) {
                @unlink($oldFile);
            }
        }
        $filename = 'user_' . $userId . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $uploadDir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            set_flash('danger', 'Nao foi possivel salvar a foto.');
            $this->redirect('/?r=hostess/perfil');
        }

        $publicPath = '/uploads/profiles/' . $filename;
        $userModel = new UserModel();
        $userModel->updatePhoto($userId, $publicPath, $userId);

        $_SESSION['user']['foto_path'] = $publicPath;
        set_flash('success', 'Foto atualizada com sucesso.');
        $this->redirect('/?r=hostess/perfil');
    }

    private function levelFor(int $completed): string
    {
        if ($completed >= 60) {
            return 'Platina';
        }
        if ($completed >= 30) {
            return 'Ouro';
        }
        if ($completed >= 10) {
            return 'Prata';
        }
        return 'Bronze';
    }
}
