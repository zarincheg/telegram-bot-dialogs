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

1. Create a Dialog class
2. [Create a Telegram command](https://telegram-bot-sdk.readme.io/docs/commands-system) and start a Dialog from `Command::handle()`.
3. Setup your controller class to proceed active Dialog on income webhook request.


### 1. Create a Dialog class

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


### 2. Create a Telegram command

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
        Dialogs::activate(new HelloDialog($this->update));
    }
}
```


### 3. Setup your controller

Process request inside your Laravel webhook controller:

```php
use Telegram\Bot\Api;
use Telegram\Bot\BotsManager;
use KootLabs\TelegramBotDialogs\Dialogs;

final class TelegramWebhookController
{
    public function handle(Api $telegram, Dialogs $dialogs, BotsManager $botsManager): void
    {
        $update = $telegram->commandsHandler(true);

        $dialogs->exists($update)
            ? $dialogs->proceed($update)
            : $botsManager->bot('your-bot-name')->sendMessage([ // fallback message
                'chat_id' => $update->getChat()->id,
                'text' => 'There is no active dialogs at this moment.',
            ]);
    }
}
```

### Available methods of the _Dialog_ class

- `start()` - Start the dialog from the first step
- `proceed()` - Proceed the dialog to the next step
- `end()` - End dialog
- `isEnd()` - Check the end of the dialog
- `jump(string $step)` - Jump to the particular step, where `$step` is the `public` method name
- `remember(string $key, mixed $value)` - Add a new key-value to `Dialog::$memory` array to make this data available on next steps


### Available methods of the _Dialogs_ class

- `activate(\KootLabs\TelegramBotDialogs\Dialog $dialog)` - Activate a new Dialog (to start it - call `proceed()`)
- `proceed(\Telegram\Bot\Objects\Update $update)` - Run the next step handler for the existing Dialog
- `exists(\Telegram\Bot\Objects\Update $update)` - Check for existing Dialog


## ToDo

- Add tests
- Add AI API support (e.g. [LUIS](https://www.luis.ai/), [Dataflow](https://cloud.google.com/dataflow))
- Make package fully Laravel-independent
- Improve documentation and examples
