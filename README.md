# telegram-bot-dialogs
The extension for Telegram Bot API PHP SDK that allows to implement dialogs in bots

This libriary allows to make simple dialogs for your Telegram bots that based on the Telegram Bot API - PHP SDK (https://github.com/irazasyed/telegram-bot-sdk).

###Installation
You can easy install the package using Composer:

`composer require zarincheg/telegram-bot-dialogs dev-master`

After that you need to register the service provide for using with Laravel DI container
Open config/app.php and, to your providers array at the bottom, add:

```php
Telegram\Bot\Laravel\TelegramServiceProvider::class
```

Each dialog should be implemented as class that extends basic Dialog as you can see in example bellow:

```php
<?php
use BotDialogs\Dialog;

/**
 * Class HelloDialog
 */
class HelloDialog extends Dialog
{
    // Array with methods that contains logic of dialog steps.
    // The order in this array defines the sequence of execution.
    protected $steps = ['hello', 'fine', 'bye'];

    public function hello()
    {
        $this->telegram->sendMessage([
            'chat_id' => $this->getChat()->getId(),
            'text' => 'Hello! How are you?'
        ]);
    }

    public function bye()
    {
        $this->telegram->sendMessage([
            'chat_id' => $this->getChat()->getId(),
            'text' => 'Bye!'
        ]);
        $this->jump('hello');
    }

    public function fine()
    {
        $this->telegram->sendMessage([
            'chat_id' => $this->getChat()->getId(),
            'text' => 'I\'m OK :)'
        ]);
    }
}
```

For initiate new dialog you have to use Dialogs class instance to add new dialog implementation. And for execute the first and next steps you have to call Dialogs::procceed() mehod with update object as an argument. Also it is possible to use dialogs with Telegram commands and DI through type hinting.

```php
use Telegram\Bot\Commands\Command;
use BotDialogs\Dialogs;
use App\Dialogs\HelloDialog;

class HelloCommand extends Command
{
    /**
     * @var string Command name
     */
    protected $name = 'hello';
    protected $description = 'Just say "Hello" and ask few questions';

    /**
     * @param Dialogs $dialogs
     */
    public function __construct(Dialogs $dialogs)
    {
        $this->dialogs = $dialogs;
    }

    public function handle($arguments)
    {
        $this->dialogs->add(new HelloDialog($this->update));
    }
}
```
And code in the webhook controller
```php
use Telegram\Bot\Api;
use BotDialogs\Dialogs;

// ...

public function __construct(Api $telegram, Dialogs $dialogs)
{
  $this->telegram = $telegram;
  $this->dialogs = $dialogs;
}
    
// ...
    
$update = $this->telegram->commandsHandler(true);

if (!$this->dialogs->exists($update)) {
  // Do something if there are no existing dialogs
} else {
  // Call the next step of the dialog
  $this->dialogs->proceed($update);
}
```
For storing dialog information(also for the data that pushed by the Dialog::remember() method) using Redis.

###Available methods of the _Dialog_ class

- `start()` - Start the dialog from the first step
- `proceed()` - Proceed the dialog to the next step
- `end()` - End dialog
- `jump($step)` - Jump to the particular step
- `remember($value)` - Remember some information for the next step usage (For now just a "short" memory works, just for one step)
- `isEnd()` - Check the end of the dialog

###Available methods of the _Dialogs_ class
- `add(Dialog $dialog)` - Add the new dialog
- `get(Telegram\Bot\Objects\Update $update)` - Returns the dialog object for the existing dialog
- `proceed(Telegram\Bot\Objects\Update $update)` - Run the next step handler for the existing dialog
- `exists(Telegram\Bot\Objects\Update $update)` - Check for existsing dialog

##What is planned to improve:
- Add default response messages (if you need just a text answers from the bot, without any background logic)
- Refactor for using names in Dialogs::add() instead of objects and rename to start()
- Add LUIS API support (https://www.luis.ai/)
- Long-term memory