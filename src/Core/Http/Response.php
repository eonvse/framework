<?php
declare(strict_types=1);

namespace Core\Http;

use Core\Exceptions\HttpException;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private mixed $content = '';

    public function __construct()
    {
        $this->statusCode = 200;
        $this->headers = [
            'Content-Type' => 'text/html' // Значение по умолчанию
        ];
        $this->content = '';
    }


    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function json(array $data): self
    {
        $this->header('Content-Type', 'application/json');
        $this->content = json_encode($data);
        return $this;
    }

    public function redirect(string $url, int $status = 302): self
    {
        $this->setStatusCode($status);
        $this->header('Location', $url);
        return $this;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);
        
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        
        echo $this->content;
    }

     /**
     * Устанавливает содержимое ответа с поддержкой различных типов
     * 
     * @param mixed $content Контент ответа:
     *     - string: используется как есть
     *     - resource: читается как поток
     *     - объекты с __toString: преобразуются в строку
     *     - null: очищает контент
     * @param string $contentType Необязательный MIME-тип контента
     * @return self
     */
    public function setContent(mixed $content, ?string $contentType = null): self
    {
        // Очистка контента
        if ($content === null) {
            $this->content = '';
            return $this;
        }

        // Обработка ресурсов
        if (is_resource($content)) {
            $this->content = stream_get_contents($content);
            if ($contentType === null) {
                $this->header('Content-Type', 'application/octet-stream');
            }
            return $this;
        }

        // Автоматическое преобразование объектов
        if (is_object($content)) {
            if (method_exists($content, '__toString')) {
                $this->content = (string)$content;
                return $this;
            }

            throw new \InvalidArgumentException(
                'Объекты должны реализовывать __toString()'
            );
        }

        // Установка типа контента если передан
        if ($contentType !== null) {
            $this->header('Content-Type', $contentType);
        }

        $this->content = $content;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function __toString(): string
    {
        return $this->content;
    }

    public static function error(string $message, int $status = 400): HttpException
    {
        return (new self())->setStatusCode($status)
            ->json(['error' => $message])
            ->toException();
    }

    public function toException(): HttpException
    {
        return new HttpException($this, $this->content['error'] ?? '', $this->statusCode);
    }
}