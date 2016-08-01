<?php
/**
 * Created by Kirill Zorin <zarincheg@gmail.com>
 * Personal website: http://libdev.ru
 *
 * Date: 12.06.2016
 * Time: 16:47
 */

namespace BotDialogs;

use Exception;
use Illuminate\Support\Facades\Config;
use Telegram\Bot\Actions;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

/**
 * Class Dialog
 * @package BotDialogs
 */
class Dialog
{
    protected $steps = []; // @todo Add the feature that allow to write response messages inside array for simple "chat-only" dialogs
    /**
     * @var int Next step
     */
    protected $next = 0;
    protected $current = 0;
    protected $yes = null;
    protected $no = null;

    /**
     * @param int $next
     */
    public function setNext($next)
    {
        $this->next = $next;
    }

    /**
     * @return array
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * @return int
     */
    public function getNext()
    {
        return $this->next;
    }
    /**
     * @var Api
     */
    protected $telegram;

    /**
     * @param Api $telegram
     */
    public function setTelegram(Api $telegram)
    {
        $this->telegram = $telegram;
    }
    /**
     * @var Update
     */
    protected $update;
    protected $memory = '';

    /**
     * @param string $memory
     */
    public function setMemory($memory)
    {
        $this->memory = $memory;
    }

    /**
     * @return string
     */
    public function getMemory()
    {
        return $this->memory;
    }

    /**
     * @param Update $update
     */
    public function __construct(Update $update)
    {
        $this->update = $update;
    }


    public function start()
    {
        $this->next = 0;
        $this->proceed();
    }

    /**
     * @return bool
     * @throws Exception
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function proceed()
    {
        $this->current = $this->next;

        if (!$this->isEnd()) {
            $this->telegram->sendChatAction([
                'chat_id' => $this->update->getMessage()->getChat()->getId(),
                'action' => Actions::TYPING
            ]);

            $step = $this->steps[$this->current];

            if (is_array($step)) {
                if (!isset($step['name'])) {
                    // @todo Replace by the specific exception.
                    throw new Exception('Dialog step name must be defined.');
                }

                $name = $step['name'];
            } elseif(is_string($step)) {
                $name = $step;
            } else {
                throw new Exception('Dialog step is not defined.');
            }

            // @todo Refactor: Extract method
            // Flush yes/no state
            $this->yes = null;
            $this->no = null;

            if (is_array($step) && isset($step['is_dich']) && $step['is_dich']) {
                $message = $this->update->getMessage()->getText();
                $message = mb_strtolower(trim($message));
                $message = preg_replace('![%#,:&*@_\'\"\\\+\^\(\)\[\]\-\$\!\?\.]+!ui', '', $message);

                if (in_array($message, Config::get('dialogs.aliases.yes'))) {
                    $this->yes = true;
                } elseif (in_array($message, Config::get('dialogs.aliases.no'))) {
                    $this->no = true;
                }
            }

            $this->$name();

            // Step forward only if did not changes inside the step handler
            if ($this->next == $this->current) {
                $this->next++;
            }
        }
    }

    /**
     * Jump to the particular step of the dialog
     * @param $step
     */
    public function jump($step)
    {
        foreach ($this->steps as $index => $value) {
            if ((is_array($value) && $value['name'] === $step) || $value === $step) {
                $this->setNext($index);
                break;
            }
        }
    }

    /**
     * @todo Maybe the better way is that to return true/false from step-methods. And if it returns false - it means end of dialog
     */
    public function end()
    {
        $this->next = count($this->steps);
    }

    /**
     * Remember information for the next step usage. It works with Dialogs management class that store data to Redis.
     * @param $value
     * @return mixed
     */
    public function remember($value = '')
    {
        if (!$value && $this->memory !== '') {
            return json_decode($this->memory);
        }

        $this->memory = json_encode($value);
    }

    /**
     * Check if dialog ended
     * @return bool
     */
    public function isEnd()
    {
        if ($this->next >= count($this->steps)) {
            return true;
        }

        return false;
    }

    /**
     * Returns Telegram chat object
     * @return \Telegram\Bot\Objects\Chat
     */
    public function getChat()
    {
        return $this->update->getMessage()->getChat();
    }

    /**
     * @param $name
     * @param $args
     * @return bool
     */
    public function __call($name, $args)
    {
        // @todo Add logging
        if (isset($this->steps[$this->current]['response'])) {
            $this->telegram->sendMessage([
                'chat_id' => $this->getChat()->getId(),
                'text' => $this->steps[$this->current]['response']
            ]);
        }

        return false;
    }
}