<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Tests;

use KootLabs\TelegramBotDialogs\Dialog;
use KootLabs\TelegramBotDialogs\Exceptions\InvalidDialogStep;

/** @covers \KootLabs\TelegramBotDialogs\Dialog */
final class DialogConfigurableStepsTest extends TestCase
{
    use CreatesUpdate;

    private const RANDOM_CHAT_ID = 42;

    /** @test */
    public function it_throws_an_exception_when_step_does_not_have_name(): void
    {
        $dialog = new class (self::RANDOM_CHAT_ID) extends Dialog {
            protected array $steps = [
                [
                    // 'name' => 'first',
                    'response' => 'Hello!',
                ],
            ];
        };

        $this->expectException(InvalidDialogStep::class);

        $dialog->proceed($this->buildUpdateOfRandomType());
    }
}
