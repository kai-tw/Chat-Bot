<?php
require_once '../class/integrated_command.php';

class StartCommand extends IntegratedCommand
{
    protected string $name = 'start';

    public function handler()
    {
        return 'Hey, there! Welcome to our bot!';
    }
}
