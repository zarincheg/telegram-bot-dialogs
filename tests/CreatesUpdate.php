<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Tests;

use Telegram\Bot\Objects\Update;

trait CreatesUpdate
{
    private function buildUpdateOfRandomType(): Update
    {
        return $this->buildTextMessageUpdate();
    }

    private function buildTextMessageUpdate(): Update
    {
        return new Update([
            'update_id' => 42,
            'message' => [
                'message_id' => 1,
                'from' => [
                    'id' => 42,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'username' => 'jdoe',
                ],
                'chat' => [
                    'id' => 42,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'username' => 'jdoe',
                    'type' => 'private',
                ],
                'date' => 0,
                'text' => 'Hello from start of the epoch!',
            ],
        ]);
    }
}
