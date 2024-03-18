# Framework-agnostic installation example

```php
use Telegram\Bot\Api;
use KootLabs\TelegramBotDialogs\DialogManager;
use KootLabs\TelegramBotDialogs\Dialogs\HelloExampleDialog;
use KootLabs\TelegramBotDialogs\Storages\Drivers\RedisStore;

require __DIR__.'/vendor/autoload.php';

/** @todo replace by your token, {@see https://core.telegram.org/bots#6-botfather} */ 
$token = '110201543:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsaw';
$bot = new Api($token);
$redisStore = new RedisStore('127.0.0.1', 6379);

$dialogManager = new DialogManager($bot, $redisStore);

$dialog = new HelloExampleDialog($this->update->getChat()->id, $bot);
$dialogManager->activate($dialog);
$dialogManager->proceed($dialog);
```
