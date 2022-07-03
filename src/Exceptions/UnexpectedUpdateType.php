<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Exceptions;

/**
 * Used to ignore some Update types: when thrown, the cursor will not be moved to the next step.
 */
final class UnexpectedUpdateType extends \DomainException implements DialogException
{
}
