<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs;

use Illuminate\Redis\RedisManager;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

final class Dialogs
{
    private const REDIS_PREFIX = 'tg_dialog_';

    private Api $telegram;

    private RedisManager $redis;

    public function __construct(Api $telegram, RedisManager $redis)
    {
        $this->telegram = $telegram;
        $this->redis = $redis;
    }

    /** @deprecated Please use \KootLabs\TelegramBotDialogs\Dialogs::activate() instead. It will be removed in v0.4.0 */
    public function add(Dialog $dialog): Dialog
    {
        $this->activate($dialog);
        return $dialog;
    }

    public function activate(Dialog $dialog): void
    {
        $dialog->setTelegram($this->telegram);
        $this->storeDialogState($dialog);
    }

    private function getDialogInstance(Update $update): ?Dialog
    {
        if (! $this->exists($update)) {
            return null;
        }

        $message = $update->getMessage();
        assert($message instanceof \Telegram\Bot\Objects\Message);
        $chatId = $message->chat->id;

        $next = $this->getDialogData($chatId, 'next');
        if (! is_numeric($next)) {
            throw new \RuntimeException('Unexpected $next type');  // debug
        }

        $next = (int) $next;

        /** @var class-string<\KootLabs\TelegramBotDialogs\Dialog> $dialogFQCN */
        $dialogFQCN = $this->getDialogData($chatId, 'class');
        if (! class_exists($dialogFQCN)) {
            throw new \RuntimeException("Dialog class “{$dialogFQCN}” does not exist.");
        }

        $memory = unserialize($this->getDialogData($chatId, 'memory'), ['allowed_classes' => true]);
        assert(is_array($memory));

        /** @var \KootLabs\TelegramBotDialogs\Dialog $dialog */
        $dialog = new $dialogFQCN($update);
        $dialog->setTelegram($this->telegram);
        $dialog->setNext($next);
        $dialog->setMemory($memory);

        return $dialog;
    }

    /** Run next step of the active Dialog. */
    public function proceed(Update $update): void
    {
        $dialog = $this->getDialogInstance($update);
        if ($dialog === null) {
            return;
        }

        $chatId = $dialog->getChat()->id;
        $dialog->proceed();

        if ($dialog->isEnd()) {
            $this->redis->del(self::REDIS_PREFIX.$chatId);
        } else {
            $this->storeDialogState($dialog);
        }
    }

    public function exists(Update $update): bool
    {
        $chatId = $update->getMessage()->chat->id;
        return (bool) $this->redis->exists(self::REDIS_PREFIX.$chatId);
    }

    private function storeDialogState(Dialog $dialog): void
    {
        $chatId = $dialog->getChat()->id;

        $this->setDialogData($chatId, 'class', get_class($dialog), $dialog->ttl());
        $this->setDialogData($chatId, 'next', $dialog->getNext(), $dialog->ttl());
        $this->setDialogData($chatId, 'memory', serialize($dialog->getMemory()), $dialog->ttl());
    }

    private function setDialogData(int $chatId, string $field, mixed $value, int $ttl): void
    {
        $redis = $this->redis;

        $redis->multi();

        $redis->hset(self::REDIS_PREFIX.$chatId, $field, $value);
        $redis->expire(self::REDIS_PREFIX.$chatId, $ttl);

        $redis->exec();
    }

    private function getDialogData(int $chatId, string $field): mixed
    {
        return $this->redis->hget(self::REDIS_PREFIX.$chatId, $field);
    }
}
