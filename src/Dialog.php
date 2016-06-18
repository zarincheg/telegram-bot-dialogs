<?php
/**
 * Created by Kirill Zorin <zarincheg@gmail.com>
 * Personal website: http://libdev.ru
 *
 * Date: 12.06.2016
 * Time: 16:47
 */

namespace BotDialogs;

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
     */
    public function proceed()
    {
        if (!$this->isEnd()) {
            $this->telegram->sendChatAction([
                'chat_id' => $this->update->getMessage()->getChat()->getId(),
                'action' => Actions::TYPING
            ]);

            $name = $this->steps[$this->next];
            $this->$name();
            $this->next++;
        }
    }

    /**
     * Jump to the particular step of the dialog
     * @param $step
     */
    public function jump($step)
    {
        $key = array_search($step, $this->steps);

        if (is_integer($key)) {
            $this->setNext(array_search($step, $this->steps)-1);
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

        return false;
    }
}