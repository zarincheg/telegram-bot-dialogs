# Dialogs plugin for Telegram Bot API PHP SDK

The extension for [Telegram Bot API PHP SDK](https://github.com/irazasyed/telegram-bot-sdk) v3.1+ that allows to implement dialogs for telegram bots.


## About this fork

The goal of the fork is to maintain the package compatible with the latest [Telegram Bot API PHP SDK](https://github.com/irazasyed/telegram-bot-sdk),
PHP 8+ and Laravel features, focus on stability, better DX and readability.


## Installation

You can easily install the package using Composer:

```shell
composer require koot-labs/telegram-bot-dialogs
```


## Usage

Each dialog should be implemented as class that extends basic Dialog as you can see in example bellow:

```php
use KootLabs\TelegramBotDialogs\Dialog;

final class HelloDialog extends Dialog
{
    // List of method to execute. The order defines the sequence.
    protected array $steps = ['hello', 'fine', 'bye'];

    public function hello()
    {
        $this->telegram->sendMessage([
            'chat_id' => $this->getChat()->getId(),
            'text' => 'Hello! How are you?',
        ]);
    }

    public function fine()
    {
        $this->telegram->sendMessage([
            'chat_id' => $this->getChat()->getId(),
            'text' => "I'm OK :)",
        ]);
    }

    public function bye()
    {
        $this->telegram->sendMessage([
            'chat_id' => $this->getChat()->getId(),
            'text' => 'Bye!',
        ]);
        $this->jump('hello');
    }
}
```

For initiate new dialog you have to use Dialogs class instance to add new dialog implementation. And for execute the first and next steps you have to call Dialogs::procceed() mehod with update object as an argument. Also it is possible to use dialogs with Telegram commands and DI through type hinting.

```php
use App\Dialogs\HelloDialog;
use KootLabs\TelegramBotDialogs\Laravel\Facades\Dialogs;
use Telegram\Bot\Commands\Command;

final class HelloCommand extends Command
{
    /** @var string Command name */
    protected $name = 'hello';

    /** @var string Command description */
    protected $description = 'Just say "Hello" and ask few questions';

    public function handle(): void
    {
        Dialogs::add(new HelloDialog($this->update));
    }
}
```

And process request inside your Laravel webhook controller:
```php
use Telegram\Bot\Api;
use KootLabs\TelegramBotDialogs\Dialogs;

final class TelegramWebhookController
{
    public function handle(Api $telegram, Dialogs $dialogs): void
    {
        $update = $telegram->commandsHandler(true);

        $dialogs->exists($update)
            ? $dialogs->proceed($update)
            : $botsManager->bot('your-bot-name')->sendMessage([
                'chat_id' => $update->getChat()->id,
                'text' => 'There is no open dialog',
            ]);
    }
}
```
For storing dialog information (also for the data that pushed by the `Dialog::remember()` method) using Redis.


### Access control with in dialogs

You can inherit AuthorizedDialog class and put Telegram usernames into `$allowedUsers` property.
After that just for users in the list will be allowed to start the dialog.


### Available methods of the _Dialog_ class

- `start()` - Start the dialog from the first step
- `proceed()` - Proceed the dialog to the next step
- `end()` - End dialog
- `jump($step)` - Jump to the particular step, where `$step` is the method name (method must have `public` visibility)
- `remember(string $key, mixed $value)` - Remember key-value for next steps. It will available in `Dialog::$memory` array.
- `isEnd()` - Check the end of the dialog


### Available methods of the _Dialogs_ class

- `add(\KootLabs\TelegramBotDialogs\Dialog $dialog)` - Add the new dialog
- `get(\Telegram\Bot\Objects\Update $update)` - Returns the dialog object for the existing dialog
- `proceed(\Telegram\Bot\Objects\Update $update)` - Run the next step handler for the existing dialog
- `exists(\Telegram\Bot\Objects\Update $update)` - Check for existing dialog


## ToDo

- Add tests
- Refactor for using names in Dialogs::add() instead of objects and rename to start()
- Add AI API support (e.g. [LUIS](https://www.luis.ai/), [Dataflow](https://cloud.google.com/dataflow))
- Make package fully Laravel-independent
- Improve documentation and examples
