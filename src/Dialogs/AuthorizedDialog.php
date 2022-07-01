<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Dialogs;

use KootLabs\TelegramBotDialogs\Dialog;
use KootLabs\TelegramBotDialogs\Exceptions\DialogException;
use Telegram\Bot\Objects\Update;

abstract class AuthorizedDialog extends Dialog
{
    protected array $allowedUsers = [];

    /**
     * @todo Replace basic Exception by the specific
     * @param Update $update
     * @throws DialogException
     */
    public function __construct(Update $update)
    {
        $username = $update->getMessage()->getFrom()->getUsername();

        if (!$username || !in_array($username, $this->allowedUsers, true)) {
            throw new DialogException('You have no access to start this dialog');
        }

        parent::__construct($update);
    }
}
