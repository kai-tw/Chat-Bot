<?php
abstract class CommonCommand {
    protected string $name;
    abstract public function handle();

    public function getName() {
        return $this->name;
    }
}