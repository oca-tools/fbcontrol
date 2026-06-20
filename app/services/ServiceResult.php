<?php
declare(strict_types=1);

final class ServiceResult
{
    private bool $success;
    private string $code;
    private string $message;
    private array $payload;

    private function __construct(bool $success, string $code, string $message, array $payload = [])
    {
        $this->success = $success;
        $this->code = $code;
        $this->message = $message;
        $this->payload = $payload;
    }

    public static function success(string $message, array $payload = [], string $code = 'ok'): self
    {
        return new self(true, $code, $message, $payload);
    }

    public static function failure(string $code, string $message, array $payload = []): self
    {
        return new self(false, $code, $message, $payload);
    }

    public static function needsConfirmation(string $code, string $message, array $payload = []): self
    {
        return new self(false, $code, $message, $payload);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function payload(): array
    {
        return $this->payload;
    }
}
