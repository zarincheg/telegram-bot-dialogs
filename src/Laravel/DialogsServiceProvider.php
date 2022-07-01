<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Laravel;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use KootLabs\TelegramBotDialogs\Dialogs;

class DialogsServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /** @inheritDoc */
    public function register(): void
    {
        $this->app->singleton(Dialogs::class, static function (Container $app): Dialogs {
            return new Dialogs($app->make('telegram.bot'), $app->make('redis'));
        });

        $this->mergeConfigFrom(__DIR__.'/config/dialogs.php', 'dialogs');

        $this->app->alias(Dialogs::class, 'telegram.dialogs');
    }

    /**
     * @inheritDoc
     * @return list<string>
     */
    public function provides(): array
    {
        return ['telegram.dialogs', Dialogs::class];
    }
}
