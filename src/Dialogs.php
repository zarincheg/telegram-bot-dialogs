<?php
/**
 * Created by Kirill Zorin <zarincheg@gmail.com>
 * Personal website: http://libdev.ru
 *
 * Date: 13.06.2016
 * Time: 13:55
 */

namespace BotDialogs;

use Illuminate\Redis\RedisManager as Redis;
use Illuminate\Support\Facades\Config;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

/**
 * Class Dialogs
 * @package BotDialogs
 */
class Dialogs
{
    /**
     * @var Api
     */
    protected $telegram;

    /**
     * @var Redis
     */
    protected $redis;

    /**
     * @param Api $telegram
     * @param Redis $redis
     */
    public function __construct(Api $telegram, Redis $redis)
    {
        $this->telegram = $telegram;
        $this->redis = $redis;
    }
    /**
     * @param Dialog $dialog
     * @return Dialog
     */
    public function add(Dialog $dialog)
    {
        $dialog->setTelegram($this->telegram);

        // save new dialog
        $chatId = $dialog->getChat()->getId();
        $this->setField($chatId, 'next', $dialog->getNext());
        $this->setField($chatId, 'dialog', get_class($dialog));
        // @todo It's not safe. Need to define Dialogs registry with check of bindings

        return $dialog;
    }

    /**
     * @param Update $update
     * @return Dialog|false
     * @internal param $chatId
     */
    public function get(Update $update)
    {
        $chatId = $update->getMessage()->getChat()->getId();
        $redis = $this->redis;

        if (!$redis->exists($chatId)) {
            return false;
        }

        $next = $redis->hget($chatId, 'next');
        $name = $redis->hget($chatId, 'dialog');
        $memory = $redis->hget($chatId, 'memory');

        /** @var Dialog $dialog */
        $dialog = new $name($update); // @todo look at the todo above about code safety
        $dialog->setTelegram($this->telegram);
        $dialog->setNext($next);
        $dialog->setMemory($memory);

        return $dialog;
    }

    /**
     * @param Update $update
     */
    public function proceed(Update $update)
    {
        $dialog = self::get($update);

        if (!$dialog) {
            return;
        }
        $chatId = $dialog->getChat()->getId();
        $dialog->proceed();

        if ($dialog->isEnd()) {
            $this->redis->del($chatId);
        } else {
            $this->setField($chatId, 'next', $dialog->getNext());
            $this->setField($chatId, 'memory', $dialog->getMemory());
        }
    }

    /**
     * @param Update $update
     * @return bool
     */
    public function exists(Update $update)
    {
        if (!$this->redis->exists($update->getMessage()->getChat()->getId())) {
            return false;
        }

        return true;
    }

    /**
     * @param string $key
     * @param string $field
     * @param mixed $value
     */
    protected function setField($key, $field, $value)
    {
        $redis = $this->redis;

        $redis->multi();

        $redis->hset($key, $field, $value);
        // @todo Move to config/settings
        $redis->expire($key, 300);

        $redis->exec();
    }
}
