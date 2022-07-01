<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs;

abstract class DichotomousDialog extends Dialog
{
    protected $yes = null;
    protected $no = null;
    protected array $answerAliases = [
        'yes' => ['yes'],
        'no' => ['no'],
    ];

    /**
     * Process yes-no scenery
     * @param array $step
     * @return bool True if no further procession required (jumped to another step)
     */
    final protected function processYesNo(array $step): bool
    {
        $message = $this->update->getMessage()->text;
        $message = mb_strtolower(trim($message));
        $message = preg_replace('![%#,:&*@_\'\"\\\+^()\[\]\-\$\!?.]+!u', '', $message);

        if (in_array($message, $this->answerAliases['yes'], true)) {
            $this->yes = true;

            if (! empty($step['yes'])) {
                $this->jump($step['yes']);
                $this->proceed();

                return true;
            }
        } elseif (in_array($message, $this->answerAliases['no'], true)) {
            $this->no = true;

            if (! empty($step['no'])) {
                $this->jump($step['no']);
                $this->proceed();

                return true;
            }
        } elseif (!empty($step['default'])) {
            $this->jump($step['default']);
            $this->proceed();

            return true;
        }

        return false;
    }
}
