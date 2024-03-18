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

