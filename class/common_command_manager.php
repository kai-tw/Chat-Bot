<?php
require_once 'common_command.php';

class CommonCommandManager
{
    private array $commandList = [];

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

    public function execute(string $name)
    {
        if (strlen($name) === 0 || !isset($this->commandList[$name])) {
            return false;
        }

        return $this->commandList[$name]->handle();
    }
}
