<?php

namespace MdBlog;

use Symfony\Component\Yaml\Yaml;
use Parsedown;

class Parser {
    private $parser = null;
    private $basePath = null;
    private $inputPath = null;
    private $config = [];

    public function __construct($basePath, $inputPath) {
        $this->basePath = $basePath;
        $this->inputPath = $inputPath;
        $this->parser = new Parsedown();
    }

    private function getMarkdownFileContent($file, $fileName, $urlPath, $folder) {
        if ($file && file_exists($file)) {
            $content = FileSystem::getFileContent($file);
            preg_match_all('/---(.*?)---/s', $content, $matches);
            $yaml = $matches[1][0] ?? '';
            // parse YAML part
            $data = Yaml::parse($yaml, Yaml::PARSE_DATETIME);
            $data['urlPath'] = $urlPath;
            $data['folder'] = $folder;
            $data['url'] = '/'. $urlPath;
            $marker = strpos($content, '---', 1);
            $markdown = substr($content, $marker + 3);
            // parse Markdown part
            $html = $this->parser->text($markdown);
            return [
                'name' => $fileName,
                'content' => $html,
                'data' => $data,
            ];
        }
    }

    // private function processMarkdownFiles($postFiles, $config, $sort = false) {

    //     $posts = [];

    //     foreach ($postFiles as $file) {
    //         if ($file != '') {
    //             $fileName = basename($file, ".md");
    //             $url = basename($file);
    //             $content = FileSystem::getFileContent($file);

    //             preg_match_all('/---(.*?)---/s', $content, $matches);
    //             $yaml = $matches[1][0] ?? '';

    //             $data = Yaml::parse($yaml, Yaml::PARSE_DATETIME);
    //             if (!isset($data['url'])) {
    //                 $data['url'] = $config['url'].'/'.$fileName;
    //             }
    //             $marker = strpos($content, '---', 1);
    //             $markdown = substr($content, $marker + 3);

    //             $html = $this->parser->text($markdown);
    //             $posts[] = [
    //                 'name' => $fileName,
    //                 'content' => $html,
    //                 'data' => $data,
    //             ];
    //         }
    //     }
    //     if ($sort) {
    //         $this->sortBy($posts, 'date');
    //     }

    //     return $posts;
    // }
    // SORT by item
    // private function sortBy($posts, $item) {
    //     if ($posts) {
    //         usort($posts, function ($a, $b) use ($item) {
    //             return strcmp($b["data"][$item], $a["data"][$item]);
    //         });
    //     }
    // }
    public function getPHPExpression($code) {
        return preg_replace('~\{{\s*(.+?)\s*\}}~is', '$val = $1;', $code);
    }
    # CONFIG FILE
    public function getConfigData() {
        $fileData = [];
        $configFile = $this->basePath.'/config.yaml';
        if (file_exists($configFile)) {
            $fileYaml = FileSystem::getFileContent($configFile);
            $fileData = Yaml::parse($fileYaml);
            foreach ($fileData as &$item) {
                if (is_string($item)) {
                    $text = $this->getPHPExpression($item);
                    if (strpos($text,'$val') !== false) {
                        eval($text);
                        if (isset($val)) {
                            $item = $val;
                        }
                    }
                }
            }
        }
        return $fileData;
    }

    public function getCustomData() {
        $data = [];
        $files = FileSystem::getAllFilesInFolder($this->basePath.'/data');
        foreach ($files as $file) {
            $fileName = basename($file);
            list($name, $ext) = explode('.', $fileName);
            if ($ext == 'yaml') {
                $fileYaml = FileSystem::getFileContent($file);
                $fileData = Yaml::parse($fileYaml);
                if ($fileData) {
                    $data[$name] = $fileData;
                }
            }
        }

        return $data;
    }

    public function getAllPages() {
        $this->config = $this->getConfigData();
        $filesPaths = FileSystem::getAllFilesInFolder($this->basePath.'/pages');
        return $this->getFilesData($filesPaths);
    }

    private function getFilesData($filesPaths, $folder = '') {
        $filesData = [];
        foreach ($filesPaths as $key => $file) {
            if (is_array($file)) {
                $folderFilesData = $this->getFilesData($file, $key);
                $filesData = array_merge($filesData, $folderFilesData);
            } else {
                $fileName = basename($file);
                list($name, $ext) = explode('.', $fileName);
                if ($ext == 'md') {
                    if ($folder) {
                        $urlPath = $folder.'/'.$name;
                    } else {
                        $urlPath = $name;
                    }
                    $filesData[] = $this->getMarkdownFileContent($file, $name, $urlPath, $folder);
                }
            }
        }
        return $filesData;
    }

    // # POSTS FILES
    // public function getPosts($config) {
    //     $postFiles = [];
    //     if (is_dir($this->inputPath.'/posts')) {
    //         $postFiles = glob($this->inputPath.'/posts/*');
    //     }

    //     if (empty($postFiles)) {
    //         die('no md files in INPUT_PATH directory');
    //     }
    //     $posts = $this->processMarkdownFiles($postFiles, $config, true);

    //     return $posts;
    // }

    // # PAGES FILES
    // public function getPages($config) {
    //     $pagesFiles = [];
    //     if (is_dir($this->inputPath.'/pages')) {
    //         $pagesFiles = glob($this->inputPath.'/pages/*');
    //     }
    //     $pages = $this->processMarkdownFiles($pagesFiles, $config);

    //     return $pages;
    // }
}
