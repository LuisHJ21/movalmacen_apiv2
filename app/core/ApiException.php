<?php

class ApiException extends RuntimeException
{
    private string $errorCode;
    private int $statusCode;

    public function __construct(
        string $errorCode,
        string $message,
        int $statusCode = 400,
        ?Throwable $previous = null,
    ) {
        if ($errorCode === '') {
            throw new InvalidArgumentException('El código del error es requerido');
        }

        if ($statusCode < 400 || $statusCode > 599) {
            throw new InvalidArgumentException('El estado HTTP del error debe estar entre 400 y 599');
        }


        parent::__construct($message, 0, $previous);
        $this->errorCode = $errorCode;
        $this->statusCode = $statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
