<p align="center"><img src="https://user-images.githubusercontent.com/5278175/176997422-79e5c4c1-ff43-438e-b30e-651bb8e17bcf.png" alt="Dialogs" width="400"></p>

# Dialogs plugin for Telegram Bot API PHP SDK

The extension for [Telegram Bot API PHP SDK](https://github.com/irazasyed/telegram-bot-sdk) v3.1+ that allows to implement dialogs for telegram bots.


## About this fork

Orional package is not maintaned anymore and does not support Telegram Bot API PHP SDK v3.
The goal of the fork is to maintain the package compatible with the latest [Telegram Bot API PHP SDK](https://github.com/irazasyed/telegram-bot-sdk),
PHP 8+ and Laravel features, focus on stability, better DX and readability.


## Installation

You can easily install the package using Composer:

```shell
composer require koot-labs/telegram-bot-dialogs
```
Package requires PHP >= 8.0


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
        $this->bot->sendMessage([
            'chat_id' => $this->getChat()->id,
            'text' => 'Hello! How are you?',
        ]);
    }

    public function fine()
    {
        $this->bot->sendMessage([
            'chat_id' => $this->getChat()->id,
            'text' => "I'm OK :)",
        ]);
    }

    public function bye()
    {
        $this->bot->sendMessage([
            'chat_id' => $this->getChat()->id,
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
        Dialogs::activate(new HelloDialog(), $this->update);
    }
}
```


### 3. Setup your controller

Process request inside your Laravel webhook controller:

```php
use Telegram\Bot\BotsManager;
use KootLabs\TelegramBotDialogs\DialogManager;

final class TelegramWebhookController
{
    public function handle(DialogManager $dialogs, BotsManager $botsManager): void
    {
        $bot = $botsManager->bot('your-bot-name');
        $update = $bot->commandsHandler(true);

        // optional, for multi-bot applications only, when a given bot is not a default one
        $dialogs->setBot($bot);

        $dialogs->exists($update)
            ? $dialogs->proceed($update)
            : $botsManager->bot('your-bot-name')->sendMessage([ // fallback message
                'chat_id' => $update->getChat()->id,
                'text' => 'There is no active dialog at this moment.',
            ]);
    }
}
```


### `Dialog` class API

- `start()` - Start the dialog from the first step
- `proceed()` - Proceed the dialog to the next step
- `end()` - End dialog
- `isEnd()` - Check the end of the dialog
- `jump(string $stepName)` - Jump to the particular step, where `$step` is the `public` method name
- `remember(string $key, mixed $value)` - Add a new key-value to `Dialog::$memory` array to make this data available on next steps


### `DialogManager` class API

ℹ️ `Dialogs` [Facade](https://laravel.com/docs/master/facades) proxies calls to `DialogManager` class.

- `setBot(\Telegram\Bot\Api $bot)` - Use non-default Bot for API calls
- `activate(\KootLabs\TelegramBotDialogs\Dialog $dialog)` - Activate a new Dialog (to start it - call `proceed()`)
- `proceed(\Telegram\Bot\Objects\Update $update)` - Run the next step handler for the existing Dialog
- `exists(\Telegram\Bot\Objects\Update $update)` - Check for existing Dialog


## ToDo

- Add tests
- Add AI API support (e.g. [LUIS](https://www.luis.ai/), [Dataflow](https://cloud.google.com/dataflow))
- Improve documentation and examples
- Improve stability and DX for channel bots


## Backward compatibility promise

Dialogs is using [Semver](https://semver.org/). This means that versions are tagged with MAJOR.MINOR.PATCH.
Only a new major version will be allowed to break backward compatibility (BC).

Classes marked as `@experimental` or `@internal` are not included in our backward compatibility promise.
You are also not guaranteed that the value returned from a method is always the same.
You are guaranteed that the data type will not change.

PHP 8 introduced [named arguments](https://wiki.php.net/rfc/named_params), which increased the cost and reduces flexibility for package maintainers.
The names of the arguments for methods in Dialogs is not included in our BC promise.
