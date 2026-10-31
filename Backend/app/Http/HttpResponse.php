<?php
declare(strict_types=1);

namespace BioVet\App\Http;

final class HttpResponse
{
    public function __construct(
        public readonly int $status,
        public readonly array $headers,
        public readonly string $body
    ) {
    }

    public static function json(
        int $status,
        array $payload,
        array $headers = [],
        int $flags = 0
    ): self {
        $headers = $headers + ['Content-Type' => 'application/json; charset=utf-8'];
        $json = json_encode($payload, $flags);

        return new self($status, $headers, is_string($json) ? $json : '');
    }

    public static function empty(int $status, array $headers = []): self
    {
        return new self($status, $headers, '');
    }
}
