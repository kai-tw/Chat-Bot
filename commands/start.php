<?php
require_once '../class/common_command.php';

use Telegram\Bot\Commands\Command;

class TelegramStartCommand extends Command
{
    protected string $name = 'start';
    protected string $description = 'Start~';

    public function handle()
    {
        $this->replyWithMessage([
            'text' => (new StartCommand)->handle()
        ]);
    }
}

class StartCommand extends CommonCommand
{
    protected string $name = 'start';
    public function handle()
    {
        return 'Hey, there! Welcome to our bot!';
    }
}
