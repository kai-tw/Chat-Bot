<?php

use Telegram\Bot\Commands\Command;

abstract class IntegratedCommand extends Command
{
    protected string $name;

    /**
     * Telegram SDK will call it.
     */
    public function handle()
    {
        $this->replyWithMessage([
            'text' => $this->handler(),
        ]);
    }

    abstract public function handler();
}
