<?php

namespace App\Exceptions;

use Exception;

class WhatsappGatewayException extends Exception
{
    public function __construct(
        public readonly int $statusCode,
        string $pesanError,
    ) {
        parent::__construct("Gateway WhatsApp mengembalikan status {$statusCode}: {$pesanError}");
    }
}
