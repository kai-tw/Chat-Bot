<?php

use LINE\Webhook\Model\MessageEvent;

abstract class CommonCommand
{
    protected string $name;
    protected array $arguments = [];
    protected MessageEvent $lineEvent;

    abstract public function handle();

    public function getName()
    {
        return $this->name;
    }

    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
    }

    public function getArguments($key)
    {
        return $this->arguments[$key];
    }

    public function setLineEvent(MessageEvent $event)
    {
        $this->lineEvent = $event;
    }
}
