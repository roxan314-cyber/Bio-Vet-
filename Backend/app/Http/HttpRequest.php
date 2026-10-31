<?php
declare(strict_types=1);

namespace BioVet\App\Http;

final class HttpRequest
{
    public function __construct(
        public readonly string $method,
        private readonly array $headers,
        public readonly array $query,
        public readonly string|false $body
    ) {
    }

    public function header(string $name): ?string
    {
        foreach ($this->headers as $headerName => $value) {
            if (strcasecmp((string) $headerName, $name) === 0 && is_string($value)) {
                return $value;
            }
        }

        return null;
    }

    public static function fromGlobals(string|false $body = ''): self
    {
        $headers = [];
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers['Authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
        }
        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                $headers[$name] = $value;
            }
        }

        return new self(
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $headers,
            $_GET,
            $body
        );
    }
}
