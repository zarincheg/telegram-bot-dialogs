<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs;

use KootLabs\TelegramBotDialogs\Storages\Store;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Update;

final class DialogManager
{
    private Api $bot;

    private Store $store;

    public function __construct(Api $bot, Store $store)
    {
        $this->bot = $bot;
        $this->store = $store;
    }

    public function activate(Dialog $dialog, Update $update): void
    {
        $dialog->setUpdate($update);
        $dialog->setBot($this->bot);
        $this->storeDialogState($dialog);
    }

    /** Use non-default Bot for API calls */
    public function setBot(Api $bot): void
    {
        $this->bot = $bot;
    }

    private function getDialogInstance(Update $update): ?Dialog
    {
        if (! $this->exists($update)) {
            return null;
        }

        $message = $update->getMessage();
        assert($message instanceof \Telegram\Bot\Objects\Message);
        $chatId = $message->chat->id;

        $dialog = $this->readDialogState($chatId);
        $dialog->setUpdate($update);
        $dialog->setBot($this->bot);

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
            $this->store->delete($chatId);
        } else {
            $this->storeDialogState($dialog);
        }
    }

    /** Whether Dialog exist for a given Update. */
    public function exists(Update $update): bool
    {
        $message = $update->getMessage();
        $chatId = $message instanceof Message ? $message->chat->id : null;
        return $chatId && $this->store->has($chatId);
    }

    /** Store all Dialog. */
    private function storeDialogState(Dialog $dialog): void
    {
        $chatId = $dialog->getChat()->id;
        $this->store->set($chatId, $dialog, $dialog->ttl());
    }

    /** Restore Dialog. */
    private function readDialogState(int $chatId): Dialog
    {
        return $this->store->get($chatId);
    }
}
