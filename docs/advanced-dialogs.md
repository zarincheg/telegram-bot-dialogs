# Advanced Dialog techniques

## Advanced definition of the dialog steps

You can define default text answers for your dialog steps. For this you have to define the step as an array with name and response fields.

```php
final class HelloDialog extends Dialog
{
    protected array $steps = [
        [
            'name' => 'hello',
            'response' => 'Hello my friend!',
            'options' => ['parse_mode' => 'html'], // optional
        ],
        'fine',
        'bye',
    ];
    
    // ...
}
```
In this case, if you don't need any logic inside the step handler - you can don't define it. Just put the response inside the step definition. It works good for welcome messages, messages with tips/advices and so on. If you want format response with markdown, just set `markdown` field to `true`.

Also, you can control dialog direction in step by defining `jump ` and `end` fields. `jump` acts as `jump()` method - dialog jumps to particular step. `end` field, is set to `true`, ends dialog after current step.


## Dichotomous dialogs

⚠️ Note! This conception will be removed or re-implemented in further versions (with breaking compatibility changes). 

You can use `is_dichotomous` option of the step. If this option set to true, you can use `yes` and `no` fields of the Dialog instance to check user answer. For example:

```php
final class HelloDialog extends \KootLabs\TelegramBotDialogs\DichotomousDialog
{
    protected array $steps = [
        [
            'name' => 'hello',
            'response' => 'Hello my friend! Are you OK?',
        ],
        [
            'name' => 'answer',
            'is_dichotomous' => true,
        ],
        'bye',
    ];

    public function answer(): void
    {
        if ($this->yes) {
            // Send message "I am fine, thank you!"
        } elseif ($this->no) {
            // Send message "No, I am got a sick :("
        }
    }
}
```


Often in dichotomous question you only need to send response and jump to another step. In this case, you can define steps with responses and set their names as values of 'yes', 'no' or 'default' keys of dichotomous step. For example:

```php
final class HelloDialog extends \KootLabs\TelegramBotDialogs\DichotomousDialog
{
    protected array $steps = [
        [
            'name' => 'hello',
            'response' => 'Hello my friend! Are you OK?',
        ],
        [
            'name' => 'answer',
            'is_dichotomous' => true,
            'yes' => 'fine',
            'no' => 'sick',
            'default' => 'bye',
        ],
        [
            'name' => 'fine',
            'response' => 'I am fine, thank you!',
            'jump' => 'bye',
        ],
        [
            'name' => 'sick',
            'response' => 'No, I am got a sick :(',
        ],
        'bye',
    ];
}
```


## Access control with in dialogs

⚠️ Note! This conception will be removed or re-implemented in further versions (with breaking compatibility changes).

You can inherit `\KootLabs\TelegramBotDialogs\Dialogs\AuthorizedDialog` class and put Telegram usernames into `$allowedUsers` property.
After that just for users in the list will be allowed to start the dialog.
