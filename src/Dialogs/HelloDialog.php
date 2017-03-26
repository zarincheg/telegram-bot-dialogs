<?php
/**
 * Created by Kirill Zorin <zarincheg@gmail.com>
 * Personal website: http://libdev.ru
 *
 * Date: 13.06.2016
 * Time: 14:53
 */

namespace BotDialogs\Dialogs;

use BotDialogs\Dialog;

/**
 * Class HelloDialog
 * @package GreenzoBot\Telegram\Dialogs
 */
class HelloDialog extends Dialog
{
    protected $steps = ['hello', 'fine', 'bye'];

    public function hello()
    {
        $this->telegram->sendMessage([
            'chat_id' => $this->getChat()->getId(),
            'text' => 'Hello! How are you?'
        ]);
    }

    public function bye()
    {
        $this->telegram->sendMessage([
            'chat_id' => $this->getChat()->getId(),
            'text' => 'Bye!'
        ]);
        $this->jump('hello');
    }

    public function fine()
    {
        $this->telegram->sendMessage([
            'chat_id' => $this->getChat()->getId(),
            'text' => 'I\'m OK :)'
        ]);
    }
}
