<?php

namespace LeoT\FlarumAiSupport\Listeners;

use Flarum\Extend\Locales;
use Flarum\Foundation\Event\Validating;
use Illuminate\Contracts\Events\Dispatcher;
use LeoT\FlarumAiSupport\Console\IndexContentCommand;

class RegisterConsoleCommand
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        // 在此处可以注册控制台命令
    }
} 