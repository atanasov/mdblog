<?php
namespace MdBlog;

// Examples:
// $cli = new Cli();
// $cli->print(['blue', 'bold', 'italic','strikethrough'], "Wohoo");
// $cli->printLn(['yellow', 'italic'], " I'm invicible");
// $cli->printLn(['white', 'bold', 'redbg'], "I'm invicible");
class Cli {
    const CODES = [
        'bold'=>1,
        'italic'=>3, 'underline'=>4, 'strikethrough'=>9,
        'black'=>30, 'red'=>31, 'green'=>32, 'yellow'=>33,'blue'=>34, 'magenta'=>35, 'cyan'=>36, 'white'=>37,
        'blackbg'=>40, 'redbg'=>41, 'greenbg'=>42, 'yellowbg'=>44,'bluebg'=>44, 'magentabg'=>45, 'cyanbg'=>46, 'lightgreybg'=>47
    ];
    public static function print(array $format=[], string $text = '') {
        $formatMap = array_map(function ($v) { return self::CODES[$v]; }, $format);
        echo "\e[".implode(';',$formatMap).'m'.$text."\e[0m";
    }
    public static function printLn(array $format=[], string $text='') {
        self::print($format, $text);
        echo "\r\n";
    }
    public static function printErrorCodeLn(string $msg='', $errno, $errstr, $error_file, $error_line) {
        self::printErrorLn("$msg: Unknown error type: [$errno] $errstr - $error_file:$error_line ");
        echo "\r\n";
    }
    public static function printErrorLn(string $msg='') {
        self::printLn(['white', 'bold', 'redbg'], " $msg ");
        echo "\r\n";
    }
    public static function printWarningLn(string $msg='') {
        self::printLn(['white', 'bold', 'yellowbg'], " $msg ");
        echo "\r\n";
    }


}
