<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Storages\Drivers;

use KootLabs\TelegramBotDialogs\Storages\Store;

final class RedisStore implements Store
{
    private \Redis $redis;
    private string $host;
    private int $port;
    private string | array | null $auth;

    /**
     * Initialize Redis connection.
     * @param string|list<string>|null $auth Password or [$login, $password] array. Details: @see https://redis.io/commands/auth
     */
    public function __construct(string $host = '127.0.0.1', int $port = 6379, string | array | null $auth = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->auth = $auth;

        $this->connect();
    }

    /** @inheritDoc */
    public function set(string | int $key, mixed $value, int $ttl): void
    {
        $ttl = $ttl === 0 ? -1 : $ttl;
        $this->redis->setEx($this->decorateKey($key), $ttl, $this->serialize($value));
    }

    /** @inheritDoc */
    public function get(string | int $key): mixed
    {
        $value = $this->redis->get($this->decorateKey($key));

        return $value !== null ? $this->unserialize($value) : null;
    }

    /** @inheritDoc */
    public function has(int | string $key): bool
    {
        return (bool) $this->redis->exists($this->decorateKey($key));
    }

    /** @inheritDoc */
    public function delete(string | int $key): void
    {
        $this->redis->del($this->decorateKey($key));
    }

    /**
     * @throws \RuntimeException
     * @throws \RedisException
     */
    private function connect(): void
    {
        if (! class_exists(\Redis::class)) {
            throw new \RuntimeException('phpredis extension is required for RedisStorage');
        }

        $this->redis = new \Redis();
        $this->redis->connect($this->host, $this->port);
        if ($this->auth !== null) {
            $this->redis->auth($this->auth);
        }

        function_exists('igbinary_serialize') && defined('Redis::SERIALIZER_IGBINARY')
            ? $this->redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_IGBINARY)
            : $this->redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
    }

    /** Serialize the value. */
    private function serialize(mixed $value): string | int | float
    {
        return is_numeric($value) && !in_array($value, [\INF, -\INF], true) && !is_nan($value)
            ? $value
            : serialize($value);
    }

    /** Unserialize the value. */
    private function unserialize(string | int | float $value): mixed
    {
        return is_numeric($value)
            ? $value
            : unserialize($value, ['allowed_classes' => true]);
    }

    private function decorateKey(string | int $key): string
    {
        return sprintf('%s:%s', self::STORE_PREFIX, $key);
    }
}
