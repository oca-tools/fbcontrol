<?php
class ErrorsController extends Controller
{
    public function forbidden(): void
    {
        parent::forbidden();
    }

    public function notFound(): void
    {
        parent::notFound();
    }
}
