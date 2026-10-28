<?php

declare(strict_types=1);

if (!class_exists('Mandrill', false)) {
    class Mandrill
    {
        public string $apikey;

        public function __construct(string $apiKey)
        {
            $this->apikey = $apiKey;
        }

        public function call(string $endpoint, array $payload): array
        {
            return [];
        }
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters(string $tag, mixed $value, mixed ...$args): mixed
    {
        return $value;
    }
}
