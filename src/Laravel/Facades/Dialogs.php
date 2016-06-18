<?php
/**
 * Created by Kirill Zorin <zarincheg@gmail.com>
 * Personal website: http://libdev.ru
 * at
 *
 * Date: 18.06.2016
 * Time: 17:07
 */
namespace BotDialogs\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Dialogs
 * @package BotDialogs\Laravel\Facades
 */
class Dialogs extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'dialogs';
    }
}