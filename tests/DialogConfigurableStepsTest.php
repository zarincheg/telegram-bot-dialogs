<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Tests;

use KootLabs\TelegramBotDialogs\Dialog;
use KootLabs\TelegramBotDialogs\Exceptions\InvalidDialogStep;
use Telegram\Bot\Objects\Update;

/** @covers \KootLabs\TelegramBotDialogs\Dialog */
final class DialogConfigurableStepsTest extends TestCase
{
    /** @test */
    public function it_throws_an_exception_when_step_does_not_have_name(): void
    {
        $dialog = new class extends Dialog {
            protected array $steps = [
                [
                    // 'name' => 'first',
                    'response' => 'Hello!',
                ],
            ];
        };
        $dialog->setUpdate($this->buildTextMessageUpdate());

        $this->expectException(InvalidDialogStep::class);

        $dialog->proceed();
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
