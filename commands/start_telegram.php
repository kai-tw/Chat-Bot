<?php
require_once 'start.php';

class StartCommand extends Telegram\Bot\Commands\Command
{
    protected string $name = 'start';

    public function handle()
    {
        $this->replyWithMessage([
            'text' => cmdStart(),
        ]);
    }
}
