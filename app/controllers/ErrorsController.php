<?php
class ErrorsController extends Controller
{
    public function forbidden(string $message = 'OOps, acesso não autorizado.'): void
    {
        parent::forbidden($message);
    }

    public function notFound(string $message = 'OOps, página não encontrada.'): void
    {
        parent::notFound($message);
    }
}
