<?php

use LINE\Webhook\Model\MessageEvent;

require_once 'common_command.php';

class CommonCommandManager
{
    private array $commandList = [];
    private MessageEvent $lineEvent;

    public function addCommand(CommonCommand $command)
    {
        $this->commandList[$command->getName()] = $command;
    }

    public function addCommands(array $commandArray)
    {
        foreach ($commandArray as $command) {
            $this->addCommand($command);
        }
    }

    public function execute(string $name, array $arguments = [])
    {
        if (strlen($name) === 0 || !isset($this->commandList[$name])) {
            return false;
        }
        $this->commandList[$name]->setLineEvent($this->lineEvent);
        $this->commandList[$name]->setArguments($arguments);
        return $this->commandList[$name]->handle();
    }

    public function setLineEvent(MessageEvent $event)
    {
        $this->lineEvent = $event;
    }
}
