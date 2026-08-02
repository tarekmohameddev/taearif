<?php

declare(strict_types=1);

namespace App\Domain\Ai\DTOs;

final class LlmMessage
{
    public function __construct(
        public readonly string $role,   // system|user|assistant|tool
        public readonly string $content,
        public readonly ?string $name = null,
    ) {}

    public function toArray(): array
    {
        $arr = ['role' => $this->role, 'content' => $this->content];
        if ($this->name !== null) {
            $arr['name'] = $this->name;
        }
        return $arr;
    }

    public static function system(string $content): self
    {
        return new self('system', $content);
    }

    public static function user(string $content): self
    {
        return new self('user', $content);
    }

    public static function assistant(string $content): self
    {
        return new self('assistant', $content);
    }
}
