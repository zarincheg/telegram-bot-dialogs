<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Laravel;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use KootLabs\TelegramBotDialogs\DialogManager;
use KootLabs\TelegramBotDialogs\Laravel\Stores\RedisStoreAdapter;
use KootLabs\TelegramBotDialogs\Storages\Store;

final class DialogsServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /** @inheritDoc */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/telegramdialogs.php', 'telegramdialogs');

        $this->offerPublishing();
        $this->registerBindings();
    }

    /** Setup the resource publishing groups. */
    private function offerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/config/telegramdialogs.php' => config_path('telegramdialogs.php'),
            ], 'telegram-config');
        }
    }

    private function registerBindings(): void
    {
        $this->app->singleton(Store::class, static function (Container $app): Store {
            $config = $app->get('config');
            $connection = $app->make('redis')->connection($config->get('telegramdialogs.stores.redis.connection'));
            return new RedisStoreAdapter($connection);
        });

        $this->app->singleton(DialogManager::class, static function (Container $app): DialogManager {
            return new DialogManager($app->make('telegram.bot'), $app->make(Store::class));
        });

        $this->app->alias(DialogManager::class, 'telegram.dialogs');
    }

    /**
     * @inheritDoc
     * @return list<string>
     */
    public function provides(): array
    {
        return [
            'telegram.dialogs',
            DialogManager::class,
            Store::class,
        ];
    }
}
