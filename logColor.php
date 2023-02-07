<?php

class Log
{

    public function __construct()
    {
    }

    public function error($text)
    {
        echo "[\033[31m ERR\033[0m ] $text\n";
    }

    public function success($text)
    {
        echo "[\033[32m OK\033[0m ] $text\n";
    }

    public function warning($text)
    {
        echo "[\033[33m WARN\033[0m ] $text\n";
    }

    public function info($text)
    {
        echo "[\033[36m INFO\033[0m ] $text\n";
    }

    public function ask($text)
    {
        echo "[\033[34m ?\033[0m ] $text";
    }

    public function debug($text)
    {
        echo "[\033[35m DEBUG\033[0m ] $text\n";
    }
}
