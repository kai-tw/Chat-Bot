<?php

use Telegram\Bot\Commands\Command;

require_once '../class/common_command.php';

class TelegramGroupIdCommand extends Command
{
    protected string $name = 'chatid';
    protected string $description = 'Get the chat ID.';
    protected array $aliases = ['groupid'];

    public function handle()
    {
        $fromId = $this->getUpdate()->getMessage()->get('chat')->get('id');
        $this->replyWithMessage([
            'text' => $fromId
        ]);
    }
}

class LineGroupIdCommand extends CommonCommand
{
    protected string $name = 'groupid';
    public function handle()
    {
        return $this->lineEvent->getSource()['GroupId'] ?? 'Cannot get the group id.';
    }
}
