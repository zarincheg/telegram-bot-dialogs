<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs;

use KootLabs\TelegramBotDialogs\Exceptions\DialogException;
use Telegram\Bot\Actions;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Chat;
use Telegram\Bot\Objects\Update;

abstract class Dialog
{
    protected Api $telegram;
    protected Update $update;
    protected array $memory = [];

    /** Seconds to store state of the dialog after latest activity on it. */
    protected int $ttl = 300;

    /** @var list<string|array<array-key, string|bool>> */
    protected array $steps = [];

    protected int $next = 0;
    protected int $current = 0;

    public function __construct(Update $update)
    {
        $this->update = $update;
    }

    /** @param int|numeric-string $next */
    final public function setNext(int | string $next): void
    {
        if (! is_numeric($next)) {
            throw new \RuntimeException("Unexpected Next value: $next; type: ".get_debug_type($next));
        }
        $this->next = (int) $next;
    }

    final public function getNext(): int
    {
        return $this->next;
    }

    final public function setTelegram(Api $telegram): void
    {
        $this->telegram = $telegram;
    }

    /** @param array<string, mixed> $memory */
    final public function setMemory(array $memory): void
    {
        $this->memory = $memory;
    }

    /** @return array<string, mixed> */
    final public function getMemory(): array
    {
        return $this->memory;
    }

    /** Start dialog from the begging. */
    final public function start(): void
    {
        $this->next = 0;
        $this->proceed();
    }

    /**
     * @throws \KootLabs\TelegramBotDialogs\Exceptions\DialogException
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    final public function proceed(): void
    {
        $this->current = $this->next;

        if ($this->isEnd()) {
            return;
        }
        $this->telegram->sendChatAction([
            'chat_id' => $this->update->getMessage()->getChat()->getId(),
            'action' => Actions::TYPING,
        ]);

        $step = $this->steps[$this->current];

        if (is_array($step)) {
            if (!isset($step['name'])) {
                throw new DialogException('Dialog step name must be defined.');
            }

            $stepName = $step['name'];
        } elseif (is_string($step)) {
            $stepName = $step;
        } else {
            throw new DialogException('Dialog step is not defined.');
        }

        if (is_array($step)) {
            if ($this instanceof DichotomousDialog) {
                // Flush yes/no state
                $this->yes = null;
                $this->no = null;
            }

            if ($this instanceof DichotomousDialog && isset($step['is_dichotomous']) && $step['is_dichotomous'] && $this->processYesNo($step)) {
                return;
            }

            if (!empty($step['jump'])) {
                $this->jump($step['jump']);
            }
        }

        if (! method_exists($this, $stepName)) {
            throw new \RuntimeException("Method “{$stepName}” is not available at the Dialog ".$this::class);
        }

        $this->$stepName($step);

        // Step forward only if did not changes inside the step handler
        if ($this->next === $this->current) {
            ++$this->next;
        }
    }

    /** Jump to the particular step of the dialog */
    final public function jump(string $step): void
    {
        foreach ($this->steps as $index => $value) {
            if ($value === $step || (is_array($value) && $value['name'] === $step)) {
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

    /** Check if dialog ended */
    final public function isEnd(): bool
    {
        return $this->next >= count($this->steps);
    }

    /** Returns Telegram Chat */
    final public function getChat(): Chat
    {
        return $this->update->getMessage()->getChat();
    }

    /**
     * @return bool
     * @throws \KootLabs\TelegramBotDialogs\Exceptions\DialogException
     */
    public function __call(string $name, array $args)
    {
        if (count($args) === 0) {
            return false;
        }

        $step = $args[0];

        if (!is_array($step)) {
            throw new DialogException('For string steps method must be defined.');
        }

        // @todo Add logging
        if (isset($step['response'])) {
            $params = [
                'chat_id' => $this->getChat()->getId(),
                'text' => $step['response'],
            ];

            if (isset($step['markdown']) && $step['markdown']) {
                $params['parse_mode'] = 'Markdown';
            }

            $this->telegram->sendMessage($params);
        }

        if (!empty($step['jump'])) {
            $this->jump($step['jump']);
        }

        if (isset($step['end']) && $step['end']) {
            $this->end();
        }

        return true;
    }

    final public function ttl(): int
    {
        return $this->ttl;
    }
}
