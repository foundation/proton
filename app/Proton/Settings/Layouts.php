<?php

namespace App\Proton\Settings;

class Layouts
{
    /**
     * @param array<string, string> $rules
     */
    public function __construct(
        public string $default = 'default.html',
        public array $rules = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            default: $data['default'] ?? 'default.html',
            rules: (array)($data['rules'] ?? []),
        );
    }
}
