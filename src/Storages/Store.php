<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Storages;

interface Store
{
    public const STORE_PREFIX = 'tg:dialog:';

    /** Save an item in the storage with a specific key and data. */
    public function set(string | int $key, mixed $value, int $ttl): void;

    /** Retrieve an item from the storage by key. */
    public function get(string | int $key): mixed;

    /** Whether a key exist in a Storage. */
    public function has(string | int $key): bool;

    /** Delete a stored item by its key. */
    public function delete(string | int $key): void;
}
