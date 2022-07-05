<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs;

use KootLabs\TelegramBotDialogs\Exceptions\InvalidDialogStep;
use KootLabs\TelegramBotDialogs\Exceptions\UnexpectedUpdateType;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Chat;
use Telegram\Bot\Objects\Update;

abstract class Dialog
{
    protected Api $bot;
    protected Update $update;
    protected array $memory = [];

    /** Seconds to store state of the Dialog after latest activity on it. */
    protected int $ttl = 300;

    /** @var list<string|array<array-key, string|bool>> */
    protected array $steps = [];

    protected int $next = 0;
    protected int $current = 0;

    /**
     * Specify index of the next step to be used on next income message.
     * @param positive-int $next Step index.
     */
    final protected function setNext(int $next): void
    {
        $this->next = $next;
    }

    /**
     * Get index of the next step.
     * @deprecated There are no any known use cases. Can be removed.
     */
    final public function getNext(): int
    {
        return $this->next;
    }

    /** Specify context info for the Dialog. */
    final public function setUpdate(Update $update): void
    {
        $this->update = $update;
    }

    /** Specify bot instance (for multi-bot applications). */
    final public function setBot(Api $bot): void
    {
        $this->bot = $bot;
    }

    /** Start Dialog from the begging. */
    final public function start(): void
    {
        $this->next = 0;
        $this->proceed();
    }

    /**
     * @throws \KootLabs\TelegramBotDialogs\Exceptions\InvalidDialogStep
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    final public function proceed(): void
    {
        $this->current = $this->next;

        if ($this->isEnd()) {
            return;
        }

        $stepNameOrConfig = $this->steps[$this->current];

        if (is_array($stepNameOrConfig)) {
            $this->proceedConfiguredStep($stepNameOrConfig);
        } elseif (is_string($stepNameOrConfig)) {
            $stepMethodName = $stepNameOrConfig;

            if (! method_exists($this, $stepMethodName)) {
                throw new InvalidDialogStep(sprintf('Public method “%s::%s()” is not available.', $this::class, $stepMethodName));
            }

            try {
                $this->$stepMethodName();
            } catch (UnexpectedUpdateType) {
                return; // skip moving to the next step
            }
        } else {
            throw new InvalidDialogStep('Unknown format of the step.');
        }

        // Step forward only if did not change inside the step handler
        if ($this->next === $this->current) {
            ++$this->next;
        }
    }

    /** Jump to the particular step of the Dialog. */
    final protected function jump(string $stepName): void
    {
        foreach ($this->steps as $index => $value) {
            if ($value === $stepName || (is_array($value) && $value['name'] === $stepName)) {
                $this->setNext($index);
                break;
            }
        }
    }

    /**
     * @todo Maybe the better way is that to return true/false from step-methods.
     * @todo ...And if it returns false - it means end of dialog
     */
    final public function end(): void
    {
        $this->next = count($this->steps);
    }

    /** Remember information for next steps. */
    final public function remember(string $key, mixed $value): void
    {
        $this->memory[$key] = $value;
    }

    /** Check if Dialog ended */
    final public function isEnd(): bool
    {
        return $this->next >= count($this->steps);
    }

    /** Returns Telegram Chat */
    final public function getChat(): Chat
    {
        return $this->update->getMessage()->chat;
    }

    final public function ttl(): int
    {
        return $this->ttl;
    }

    /**
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     * @throws \KootLabs\TelegramBotDialogs\Exceptions\InvalidDialogStep
     */
    private function proceedConfiguredStep(array $stepConfig): void
    {
        if (!isset($stepConfig['name'])) {
            throw new InvalidDialogStep('Configurable Dialog step does not contain required “name” value.');
        }

        if (isset($stepConfig['response'])) {
            $params = [
                'chat_id' => $this->getChat()->id,
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

        if (isset($stepConfig['end']) && $stepConfig['end'] === true) {
            $this->end();
        }
    }

    /** @return array<string, mixed> */
    public function __serialize(): array
    {
        return [
            'next' => $this->next,
            'memory' => $this->memory,
        ];
    }
}
