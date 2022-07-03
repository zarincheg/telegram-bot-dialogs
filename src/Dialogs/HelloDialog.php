<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Dialogs;

use KootLabs\TelegramBotDialogs\Dialog;

/**
 * An example of Dialog class for demo purposes.
 * @internal
 */
final class HelloDialog extends Dialog
{
    protected array $steps = ['hello', 'fine', 'bye'];

    public function hello(): void
    {
        $this->bot->sendMessage([
            'chat_id' => $this->getChat()->id,
            'text' => 'Hello! How are you?',
        ]);
    }

    public function fine(): void
    {
        $this->bot->sendMessage([
            'chat_id' => $this->getChat()->id,
            'text' => "I'm OK :)",
        ]);
    }

    public function bye(): void
    {
        $this->bot->sendMessage([
            'chat_id' => $this->getChat()->id,
            'text' => 'Bye!',
        ]);

        if ($this->update->message->text === 'again') {
            $this->bot->sendMessage([
                'chat_id' => $this->getChat()->id,
                'text' => 'OK, one more time ;)',
            ]);

            $this->jump('hello');
        }
    }
}
