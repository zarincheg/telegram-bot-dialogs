<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs;

use KootLabs\TelegramBotDialogs\Exceptions\InvalidDialogStep;
use KootLabs\TelegramBotDialogs\Exceptions\UnexpectedUpdateType;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

abstract class Dialog
{
    protected int $chat_id;

    /** @var array<string, mixed> Key-value storage to store data between steps. */
    protected array $memory = [];

    /** @var \Telegram\Bot\Api Associated Bot instance that will perform API calls. */
    protected Api $bot;

    /** Seconds to store state of the Dialog after latest activity on it. */
    protected int $ttl = 300;

    /** @var list<string|array<array-key, string|bool>> List of steps. */
    protected array $steps = [];

    /** @var int Index of the next step. */
    protected int $next = 0;

    /** @var int|null Index of the next step that set manually using jump() method. */
    private ?int $afterProceedJumpToIndex = null;

    public function __construct(int $chatId, Api $bot = null)
    {
        $this->chat_id = $chatId;
        if ($bot) {
            $this->bot = $bot;
        }
    }

    /**
     * Specify bot instance (for multi-bot applications).
     * @internal DialogManager is the only user of this method.
     */
    final public function setBot(Api $bot): void
    {
        $this->bot = $bot;
    }

    /** Start Dialog from the begging. */
    final public function start(Update $update): void
    {
        $this->next = 0;
        $this->proceed($update);
    }

    /**
     * @throws \KootLabs\TelegramBotDialogs\Exceptions\InvalidDialogStep
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    final public function proceed(Update $update): void
    {
         $currentStepIndex = $this->next;

         if ($this->isStart()) {
             $this->beforeAllStep($update);
         }

        if ($this->isEnd()) {
            $this->afterAllStep($update);
            return;
        }

        if (! array_key_exists($currentStepIndex, $this->steps)) {
            throw new InvalidDialogStep("Undefined step with index $currentStepIndex.");
        }
        $stepNameOrConfig = $this->steps[$currentStepIndex];

        if (is_array($stepNameOrConfig)) {
            $this->proceedConfiguredStep($stepNameOrConfig, $update, $currentStepIndex);
        } elseif (is_string($stepNameOrConfig)) {
            $stepMethodName = $stepNameOrConfig;

            if (! method_exists($this, $stepMethodName)) {
                throw new InvalidDialogStep(sprintf('Public method “%s::%s()” is not available.', $this::class, $stepMethodName));
            }

            try {
                $this->beforeEveryStep($update, $currentStepIndex);
                $this->$stepMethodName($update);
                $this->afterEveryStep($update, $currentStepIndex);
            } catch (UnexpectedUpdateType) {
                return; // skip moving to the next step
            }
        } else {
            throw new InvalidDialogStep('Unknown format of the step.');
        }

        // Step forward only if did not change inside the step handler
        $hasJumpedIntoAnotherStep = $this->afterProceedJumpToIndex !== null;
        if ($hasJumpedIntoAnotherStep) {
            $this->next = $this->afterProceedJumpToIndex;
            $this->afterProceedJumpToIndex = null;
        } else {
            ++$this->next;
        }
    }

    /** @experimental Run code before all step. */
    protected function beforeAllStep(Update $update): void
    {
        // override the method to add your logic here
    }

    /** @experimental Run code after all step. */
    protected function afterAllStep(Update $update): void
    {
        // override the method to add your logic here
    }

    /** @experimental Run code before every step. */
    protected function beforeEveryStep(Update $update, int $step): void
    {
        // add experimental Dialog::beforeAllStep
        // override the method to add your logic here
    }

    /** @experimental Run code after every step. */
    protected function afterEveryStep(Update $update, int $step): void
    {
        // override the method to add your logic here
    }

    /** Jump to the particular step of the Dialog. */
    final protected function jump(string $stepName): void
    {
        foreach ($this->steps as $index => $value) {
            if ($value === $stepName || (is_array($value) && $value['name'] === $stepName)) {
                $this->afterProceedJumpToIndex = $index;
                break;
            }
        }
    }

    /** Move Dialog’s cursor to the end. */
    final public function end(): void
    {
        $this->next = count($this->steps);
    }

    /** Remember information for next steps. */
    final protected function remember(string $key, mixed $value): void
    {
        $this->memory[$key] = $value;
    }

    /** Forget information from next steps. */
    final protected function forget(string $key): void
    {
        unset($this->memory[$key]);
    }


    /** Check if Dialog started */
    final public function isStart(): bool
    {
        return $this->next === 0;
    }


    /** Check if Dialog ended */
    final public function isEnd(): bool
    {
        return $this->next >= count($this->steps);
    }

    /** Returns Telegram Chat ID */
    final public function getChatId(): int
    {
        return $this->chat_id;
    }

    /** Get a number of seconds to store state of the Dialog after latest activity on it. */
    final public function ttl(): int
    {
        return $this->ttl;
    }

    /**
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     * @throws \KootLabs\TelegramBotDialogs\Exceptions\InvalidDialogStep
     */
    private function proceedConfiguredStep(array $stepConfig, Update $update, int $currentStepIndex): void
    {
        if (!isset($stepConfig['name'])) {
            throw new InvalidDialogStep('Configurable Dialog step does not contain required “name” value.');
        }

        $this->beforeEveryStep($update, $currentStepIndex);

        if (isset($stepConfig['response'])) {
            $params = [
                'chat_id' => $this->getChatId(),
                'text' => $stepConfig['response'],
            ];

            if (!empty($stepConfig['options'])) {
                $params = array_merge($params, $stepConfig['options']);
            }

            $this->bot->sendMessage($params);
        }

        if (!empty($stepConfig['jump'])) {
            $this->jump($stepConfig['jump']);
        }

        $this->afterEveryStep($update, $currentStepIndex);

        if (isset($stepConfig['end']) && $stepConfig['end'] === true) {
            $this->end();
        }
    }

    /** @return array<string, mixed> */
    public function __serialize(): array
    {
        return [
            'chat_id' => $this->getChatId(),
            'next' => $this->next,
            'memory' => $this->memory,
        ];
    }
}
