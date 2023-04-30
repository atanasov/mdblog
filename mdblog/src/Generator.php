<?php

namespace MdBlog;


class Generator {

    private array $posts = [];
    private array $pages = [];
    private array $config = [];
    private string $basePath = '';
    private string $outputPath = '';
    private Template $template;

    public function __construct($pages, $template, $basePath, $outputPath) {
        $this->pages = $pages;
        $this->template = $template;
        $this->basePath = $basePath;
        $this->outputPath = $outputPath;
    }

    public function clearOutput() {
        // EMPTY OUTPUT FOLDER
        FileSystem::deleteAll($this->outputPath);
    }

    public function copyAssets() {
        // COPY FUNCTIONS
        // $this->template->copyFunctions();
        // COPY ASSETS
        FileSystem::copyFromTo($this->basePath.'/static', $this->outputPath.'/');
    }

    // private function getReadingTime($content) {
    //     $word = str_word_count(strip_tags($content));
    //     $m = floor($word / 200);
    //     $s = floor($word % 200 / (200 / 60));
    //     $est = $m . ' minute' . ($m == 1 ? '' : 's') . ', ' . $s . ' second' . ($s == 1 ? '' : 's');

    //     return $est;
    // }

    public function generatePages(): int {
        // OUTPUT PAGES
        foreach ($this->pages as $page) {
            $template = $page['data']['template'] ?? null;
            if ($template) {
                $pageContent = $this->template->fetch($template,[
                    'content' => $page['content'],
                    'title'=>$page['data']['title']
                ]);
                if ($page['name'] == 'index') {
                    FileSystem::writeFile($this->outputPath.'/index.html', $pageContent);
                } else {
                    FileSystem::writeFile($this->outputPath.'/'.$page['data']['urlPath'] . '/index.html', $pageContent);
                }
            } else {
                Cli::printWarningLn("Skipping page ".$page['name']. " template is not defined");
            }
        }
        return count($this->pages);
    }

    public function generateAtom() {
        $pageContent = $this->template->fetch('atom.xml',['posts' => $this->posts]);
        FileSystem::writeFile($this->outputPath.'/atom.xml', $pageContent);
    }


    public function generateSitemap() {
        $pageContent = $this->template->fetch('sitemap.xml',['pages' => $this->pages]);
        FileSystem::writeFile($this->outputPath.'/sitemap.xml', $pageContent);
    }


}
