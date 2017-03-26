<?php
/**
 * Created by Kirill Zorin <zarincheg@gmail.com>
 * Personal website: http://libdev.ru
 *
 * Date: 12.06.2016
 * Time: 16:47
 */

namespace BotDialogs;

use BotDialogs\Exceptions\DialogException;

use Illuminate\Support\Facades\Log;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

use Telegram\Bot\Actions;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

/**
 * Class Dialog
 * @package BotDialogs
 */
class Dialog
{
    protected $steps = [];
    /**
     * @var int Next step
     */
    protected $next = 0;
    protected $current = 0;
    protected $yes = null;
    protected $no = null;
    protected $aliases = [
        'yes' => ['yes'],
        'no' => ['no']
    ];

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
     * @param bool $autoimport
     */
    public function __construct(Update $update, bool $autoimport = false)
    {
        $this->update = $update;

        //@todo Move this call for using directly. Have to be decoupled with Laravel components.
        if ($autoimport) {
            $this->importSteps();
        }
    }


    public function start()
    {
        $this->next = 0;
        $this->proceed();
    }

    /**
     * @throws DialogException
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
                    throw new DialogException('Dialog step name must be defined.');
                }

                $name = $step['name'];
            } elseif (is_string($step)) {
                $name = $step;
            } else {
                throw new DialogException('Dialog step is not defined.');
            }

            // Flush yes/no state
            $this->yes = null;
            $this->no = null;

            if (is_array($step)) {
                if (isset($step['is_dich']) && $step['is_dich'] && $this->processYesNo($step)) {
                    return;
                } elseif (!empty($step['jump'])) {
                    $this->jump($step['jump']);
                }
            }

            $this->$name($step);

            // Step forward only if did not changes inside the step handler
            if ($this->next == $this->current) {
                $this->next++;
            }
        }
    }

    /**
     * Process yes-no scenery
     *
     * @param array $step
     *
     * @return bool True if no further procession required (jumped to another step)
     */
    protected function processYesNo(array $step)
    {
        $message = $this->update->getMessage()->getText();
        $message = mb_strtolower(trim($message));
        $message = preg_replace('![%#,:&*@_\'\"\\\+\^\(\)\[\]\-\$\!\?\.]+!ui', '', $message);

        if (in_array($message, $this->aliases['yes'])) {
            $this->yes = true;

            if (!empty($step['yes'])) {
                $this->jump($step['yes']);
                $this->proceed();

                return true;
            }
        } elseif (in_array($message, $this->aliases['no'])) {
            $this->no = true;

            if (!empty($step['no'])) {
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
     * @todo Maybe the better way is that to return true/false from step-methods.
     * @todo ...And if it returns false - it means end of dialog
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
     * @param string $name
     * @param array $args
     *
     * @return bool
     * @throws DialogException
     */
    public function __call($name, array $args)
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
                'text'    => $step['response']
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

    /**
     * @param $steps
     */
    public function setSteps($steps)
    {
        $this->steps = $steps;
    }

    /**
     * Load steps from file (php or yaml formats)
     *
     * @param string $path
     *
     * @return bool True if steps loaded successfully
     */
    public function loadSteps($path)
    {
        // @todo Have to implement scenario caching (Independent from Laravel)
        if (!file_exists($path)) {
            return false;
        }

        $ext = substr($path, strrpos($path, '.') + 1);
        switch ($ext) {
            case 'php':
                $this->setSteps(require $path);
                break;
            case 'yml':
            case 'yaml':
                $parser = new Parser();
                try {
                    $yaml = $parser->parse(file_get_contents($path));
                    $this->setSteps($yaml);
                } catch (ParseException $e) {
                    Log::error('Unable to parse YAML config: ' . $e->getMessage());

                    return false;
                }

                break;
            default:
                return false;
        }

        return true;
    }

    protected function importSteps()
    {
        // @todo Add file path argument to the method. Merge loadSteps and importSteps.
        // @todo Add config checks with scenario path optionally.
        if (is_string($this->steps) && !empty($this->steps) && is_file($this->steps)) {
            $this->loadSteps($this->steps);
        }
    }
}