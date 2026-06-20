<?php
class ErrorsController extends Controller
{
    public function forbidden(string $message = AppConstants::MESSAGE_FORBIDDEN): void
    {
        parent::forbidden($message);
    }

    public function notFound(string $message = AppConstants::MESSAGE_NOT_FOUND): void
    {
        parent::notFound($message);
    }
}
