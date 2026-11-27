<?php

namespace App\Services\Customers;

use RuntimeException;

/** Errores de validación de 来店履歴の追加・修正, con las claves y mensajes del legacy */
class VisitRecordValidationException extends RuntimeException
{
    /** @param  array<string, string>  $errors  campo => mensaje (texto japonés del legacy) */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('来店履歴の入力エラー');
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }
}
