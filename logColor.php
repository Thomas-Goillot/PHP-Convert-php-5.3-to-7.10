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
        echo "[". $this->magenta(" DEBUG ") ."] ". $text."";
    }

    public function attention($text = "")
    {
        echo "\n[\033[33m\033[1m\033[4m ATTENTION \033[0m\033[0m\033[0m] $text\n\n";
    }

    public function help($text = "")
    {
        echo "[". $this->light_magenta(" HELP ") ."] ". $this->light_magenta($text)."\n";
    }

    public function other(string $title = "OTHERS", $text = "")
    {
        echo "[ ". $this->light_blue($title) ." ] ". $text."\n";
    }

    public function red($text)
    {
        return "\033[31m$text\033[0m";
    }

    public function green($text)
    {
        return "\033[32m$text\033[0m";
    }

    public function yellow($text)
    {
        return "\033[33m$text\033[0m";
    }

    public function blue($text)
    {
        return "\033[34m$text\033[0m";
    }

    public function light_blue($text)
    {
        return "\033[94m$text\033[0m";
    }

    public function magenta($text)
    {
        return "\033[35m$text\033[0m";
    }
    
    public function light_magenta($text)
    {
        return "\033[95m$text\033[0m";
    }

    public function cyan($text)
    {
        return "\033[36m$text\033[0m";
    }

    public function white($text)
    {
        return "\033[37m$text\033[0m";
    }

    public function black($text)
    {
        return "\033[30m$text\033[0m";
    }

    public function bold($text)
    {
        return "\033[1m$text\033[0m";
    }

    public function underline($text)
    {
        return "\033[4m$text\033[0m";
    }
    public function bg_gray($text)
    {
        return "\033[47m$text\033[0m";
    }

}
