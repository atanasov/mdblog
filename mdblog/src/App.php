<?php

namespace MdBlog;

use MdBlog\Generator;
use MdBlog\Parser;
use MdBlog\Template;
use MdBlog\Cli;

class App {
    private ?string $basePath;
    private ?string $inputPath;
    private ?string $outputPath;

    public function __construct()
    {
        //set error handler
        set_error_handler([$this,"myErrorHandler"]);
    }
    // error handler function
    public static function myErrorHandler($errno, $errstr, $error_file, $error_line)
    {
        if (!(error_reporting() & $errno)) {
            // This error code is not included in error_reporting, so let it fall
            // through to the standard PHP error handler
            return false;
        }
        // $errstr may need to be escaped:
        $errstr = htmlspecialchars($errstr);
        switch ($errno) {
            case E_USER_ERROR:
                Cli::printErrorCodeLn('Error', $errno, $errstr, $error_file, $error_line);
                exit(1);
            case E_USER_WARNING:
                Cli::printErrorCodeLn('Warning', $errno, $errstr, $error_file, $error_line);
                break;
            case E_USER_NOTICE:
                Cli::printErrorCodeLn('Notice', $errno, $errstr, $error_file, $error_line);
                break;
            default:
                Cli::printErrorCodeLn('Error', $errno, $errstr, $error_file, $error_line);
                break;
        }

        Cli::printLn(['white', 'bold'], "Terminating PHP Script");
        return true;
    }
    private function validatePaths() {
        $this->basePath = $argv[1] ?? getcwd();
        $this->basePath = realpath($this->basePath);
        $this->inputPath = $argv[1] ?? null;
        if ($this->inputPath && !is_dir($this->inputPath)) {
            Cli::printErrorLn(" Input path " . $this->inputPath . " is not a directory ");
            die();
        }
        $this->outputPath = $argv[2] ?? null;
        if ($this->outputPath && !is_dir($this->outputPath)) {
            Cli::printErrorLn(" Output path " . $this->outputPath . " is not a directory ");
            die();
        }
        if ($this->inputPath == null && $this->outputPath == null) {
            $this->inputPath = $this->basePath. '/pages';
            $this->outputPath = $this->basePath. '/output';
        }
    }

    private function confirmPaths() {
        Cli::printLn(['white', 'bold'], " START RENDERING BLOG ");
        Cli::print(['white', 'bold'], " Input folder: ");
        Cli::printLn(['white', 'underline'], " $this->inputPath ");
        Cli::print(['white', 'bold'], " Output folder: ");
        Cli::printLn(['white', 'underline'], " $this->outputPath ");
        Cli::printLn(['white', 'bold'], "-------------------------------------------");
        Cli::print(['white', 'bold'], "Do you confirm? [y/n]: ");
        $confirm = readline();
        if  ($confirm == 'n') {
            die();
        }
    }

    public function run() {
        $this->validatePaths();
        $this->confirmPaths();

        $parser = new Parser($this->basePath, $this->inputPath);
        $config = $parser->getConfigData();
        $data = $parser->getCustomData();
        $pages = $parser->getAllPages();

        $template = new Template($this->basePath, $config, $pages);
        $generator = new Generator($pages, $template, $this->basePath, $this->outputPath);

        // Clear Output
        $generator->clearOutput();
        Cli::print(['white', 'bold'], "Clear output: ");
        Cli::printLn(['white', 'bold', 'greenbg'], " ✓ ");
        // Copy Assets
        $generator->copyAssets();
        Cli::print(['white', 'bold'], "Copy assets: ");
        Cli::printLn(['white', 'bold', 'greenbg'], " ✓ ");
        // Pages
        $num = $generator->generatePages();
        Cli::print(['white', 'bold'], "$num Pages: ");
        Cli::printLn(['white', 'bold', 'greenbg'], " ✓ ");
        // RSS
        $generator->generateAtom();
        Cli::print(['white', 'bold'], "RSS Feed: ");
        Cli::printLn(['white', 'bold', 'greenbg'], " ✓ ");
        // Sitemap
        $generator->generateSitemap();
        Cli::print(['white', 'bold'], "Sitemap: ");
        Cli::printLn(['white', 'bold', 'greenbg'], " ✓ ");
    }

}
