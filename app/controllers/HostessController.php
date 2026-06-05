<?php
class HostessController extends Controller
{
    private const PROFILE_PHOTO_MAX_BYTES = 2097152;
    private const PROFILE_PHOTO_MAX_PIXELS = 30000000;
    private const PROFILE_PHOTO_MAX_SIDE = 8000;

    public function turnos(): void
    {
        $this->requireAuth();
        Auth::requireRole(['hostess']);

        $shiftModel = new ShiftModel();
        $turnos = $shiftModel->listByUser(Auth::user()['id'], 50);
        usort($turnos, fn($a, $b) => strcmp($b['inicio_em'], $a['inicio_em']));

        $completed = $shiftModel->countCompletedByUser(Auth::user()['id']);
        $level = $this->levelFor($completed);

        $this->view('hostess/turnos', [
            'turnos' => $turnos,
            'completed' => $completed,
            'level' => $level,
            'flash' => get_flash(),
        ]);
    }

    public function foto(): void
    {
        $this->requireAuth();
        Auth::requireRole(['hostess']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=hostess/turnos');
        }

        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=hostess/turnos');
        }

        if (empty($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            set_flash('danger', 'Falha ao enviar a foto.');
            $this->redirect('/?r=hostess/turnos');
        }

        $file = $_FILES['foto'];
        if (!is_uploaded_file($file['tmp_name'])) {
            set_flash('danger', 'Upload inválido.');
            $this->redirect('/?r=hostess/turnos');
        }
        $receiveLimit = upload_limit_bytes(self::PROFILE_PHOTO_MAX_BYTES);
        if ((int)($file['size'] ?? 0) > $receiveLimit) {
            set_flash('danger', 'Arquivo muito grande. Máximo ' . format_bytes_ptbr($receiveLimit) . '.');
            $this->redirect('/?r=hostess/turnos');
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            set_flash('danger', 'Formato inválido. Use JPG, PNG ou WEBP.');
            $this->redirect('/?r=hostess/turnos');
        }

        if (!class_exists('finfo')) {
            set_flash('danger', 'Não foi possível validar a imagem no servidor. Avise o suporte.');
            $this->redirect('/?r=hostess/turnos');
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
            $this->redirect('/?r=hostess/turnos');
        }

        $imageInfo = @getimagesize($file['tmp_name']);
        $width = (int)($imageInfo[0] ?? 0);
        $height = (int)($imageInfo[1] ?? 0);
        if ($width <= 0 || $height <= 0) {
            set_flash('danger', 'Não foi possível validar as dimensões da imagem.');
            $this->redirect('/?r=hostess/turnos');
        }
        if ($width > self::PROFILE_PHOTO_MAX_SIDE || $height > self::PROFILE_PHOTO_MAX_SIDE || ($width * $height) > self::PROFILE_PHOTO_MAX_PIXELS) {
            set_flash('danger', 'Imagem com dimensões muito grandes. Envie uma foto menor.');
            $this->redirect('/?r=hostess/turnos');
        }

        $uploadDir = __DIR__ . '/../../public/uploads/profiles';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            set_flash('danger', 'Pasta de fotos indisponível. Avise o suporte.');
            $this->redirect('/?r=hostess/turnos');
        }
        if (!is_writable($uploadDir)) {
            set_flash('danger', 'Pasta de fotos sem permissão de gravação. Avise o suporte.');
            $this->redirect('/?r=hostess/turnos');
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
            $this->redirect('/?r=hostess/turnos');
        }

        $publicPath = '/uploads/profiles/' . $filename;
        $userModel = new UserModel();
        $userModel->updatePhoto($userId, $publicPath, $userId);

        $_SESSION['user']['foto_path'] = $publicPath;
        set_flash('success', 'Foto atualizada com sucesso.');
        $this->redirect('/?r=hostess/turnos');
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
