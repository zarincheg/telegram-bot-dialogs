<?php
/**
 * Created by Kirill Zorin <zarincheg@gmail.com>
 * Personal website: http://libdev.ru
 *
 * Date: 29.06.2016
 * Time: 17:09
 */

namespace BotDialogs\Dialogs;

use BotDialogs\Dialog;
use Exception;
use Telegram\Bot\Objects\Update;

/**
 * Class AuthorizedDialog
 * @package GreenzoBot\Telegram\Dialogs
 */
class AuthorizedDialog extends Dialog
{
    protected $allowedUsers = [];

    /**
     * @todo Replace basic Exception by the specific
     * @param Update $update
     * @throws Exception
     */
    public function __construct(Update $update)
    {
        $username = $update->getMessage()->getFrom()->getUsername();

        if (!$username || !in_array($username, $this->allowedUsers)) {
            throw new Exception('You have no access to start this dialog');
        }

        parent::__construct($update);
    }
}