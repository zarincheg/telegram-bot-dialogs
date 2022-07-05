<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Tests;

use KootLabs\TelegramBotDialogs\Dialog;
use KootLabs\TelegramBotDialogs\Exceptions\InvalidDialogStep;

/** @covers \KootLabs\TelegramBotDialogs\Dialog */
final class DialogTest extends TestCase
{
    /** @test */
    public function it_end_after_process_of_a_single_step_dialog(): void
    {
        $dialog = new class extends Dialog {
            protected array $steps = ['existingMethod'];

            public function existingMethod()
            {}
        };

        $dialog->start();

        $this->assertTrue($dialog->isEnd());
    }

    /** @test */
    public function it_end_after_process_of_a_multi_step_dialog(): void
    {
        $dialog = new class extends Dialog {
            protected array $steps = ['existingMethodA', 'existingMethodB'];

            public function existingMethodA()
            {}

            public function existingMethodB()
            {}
        };

        $dialog->start();
        $dialog->proceed();

        $this->assertTrue($dialog->isEnd());
    }

    /** @test */
    public function it_throws_custom_exception_when_method_not_defined(): void
    {
        $dialog = new class extends Dialog {
            protected array $steps = ['unknownMethodName'];
        };

        $this->expectException(InvalidDialogStep::class);

        $dialog->start();
    }
}
