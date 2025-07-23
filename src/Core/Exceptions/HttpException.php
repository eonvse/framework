<?php
declare(strict_types=1);

namespace Core\Exceptions;

use Core\Http\Response;

class HttpException extends \Exception
{
    public function __construct(
        protected Response $response,
        string $message = "",
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}