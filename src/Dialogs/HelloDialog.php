<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Dialogs;

use KootLabs\TelegramBotDialogs\Dialog;

/** An example of Dialog class for demo purposes. */
final class HelloDialog extends Dialog
{
    protected array $steps = ['hello', 'fine', 'bye'];

    public function hello(): void
    {
        $this->telegram->sendMessage([
            'chat_id' => $this->getChat()->getId(),
            'text' => 'Hello! How are you?',
        ]);
    }

    public function fine(): void
    {
        $this->telegram->sendMessage([
            'chat_id' => $this->getChat()->getId(),
            'text' => "I'm OK :)",
        ]);
    }

    public function bye(): void
    {
        $this->telegram->sendMessage([
            'chat_id' => $this->getChat()->getId(),
            'text' => 'Bye!',
        ]);

        if ($this->update->message->text === 'again') {
            $this->telegram->sendMessage([
                'chat_id' => $this->getChat()->getId(),
                'text' => 'OK, one more time ;)',
            ]);

            $this->jump('hello');
        }
    }
}
