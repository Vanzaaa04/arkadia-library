<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Interface SerializableEntityInterface
 * Kontrak untuk serialisasi Object Class PHP ke bentuk Array/JSON.
 */
interface SerializableEntityInterface
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
