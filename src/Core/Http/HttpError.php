<?php
declare(strict_types=1);

namespace Core\Http;

use Core\Exceptions\HttpException;

class HttpError
{
    public static function notFound(string $message = 'Not Found'): HttpException
    {
        $response = (new Response())
            ->setStatusCode(404)
            ->json(['error' => $message]);

        return new HttpException($response, $message, 404);
    }

    public static function invalidArgument(string $message): HttpException
    {
        $response = (new Response())
            ->setStatusCode(400)
            ->json(['error' => $message]);

        return new HttpException($response, $message, 400);
    }

}