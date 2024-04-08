<?php

use Telegram\Bot\Commands\Command;

require_once '../class/common_command.php';

class TelegramUserIdCommand extends Command
{
    protected string $name = 'userid';
    protected string $description = 'Get my user ID.';

    public function handle()
    {
        $fromId = $this->getUpdate()->getMessage()->get('from')->get('id');
        $this->replyWithMessage([
            'text' => $fromId
        ]);
    }
}

class LineUserIdCommand extends CommonCommand
{
    protected string $name = 'userid';
    public function handle()
    {
        $source = $this->lineEvent->getSource();
        return $source->getUserId() ?? 'Cannot get your userId.';
    }
}
