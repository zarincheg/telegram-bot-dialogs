<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Dialogs;

use KootLabs\TelegramBotDialogs\Dialog;
use Telegram\Bot\Objects\Update;

/**
 * An example of Dialog class for demo purposes.
 * @internal
 */
abstract class AuthorizedDialog extends Dialog
{
    protected array $allowedUsers = [];

    public function __construct(Update $update)
    {
        $username = $update->getMessage()->getFrom()->getUsername();

        if (!$username || !in_array($username, $this->allowedUsers, true)) {
            throw new \Exception('You have no access to start this dialog');
        }

        parent::__construct($update);
    }
}
