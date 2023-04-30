<?php

namespace MdBlog;

use DateTime;

class Template {

    public $blocks = array();
    public $cachePath;
    public $basePath;
    public $cache_enabled = FALSE;
    public $hasFunctions = false;
    public $templatesPath;
    public $config;
    public $pages;

    public function __construct($basePath, $config, $pages)
    {
        $this->templatesPath = $basePath . '/templates/';
        $this->cachePath = $basePath . '/cache/';
        $this->basePath = $basePath;
        $this->config = $config;
        $this->pages = $pages;
        include ($this->basePath . '/custom.php');
    }

    public function copyFunctions() {
        if (file_exists($this->basePath . '/custom.php')) {
            FileSystem::copyFile($this->basePath . '/custom.php', $this->cachePath.'/custom.php');
        }
    }

    public function view($file, $data = array()) {
        $cached_file = $this->cache($file);
        extract($data, EXTR_SKIP);
        require $cached_file;
    }

    public function fetch($file, $data = array()) {
        $cached_file = $this->cache($file);
        $variables = array_merge($this->config, $data);
        $variables['pages'] = $this->pages;
        extract($variables, EXTR_SKIP);
        // extract($data, EXTR_OVERWRITE);
        $filterPages = function ($options, $limit) { return $this->filterPages($options, $limit);};
        ob_start();
        require $cached_file;
        $scriptContent = ob_get_contents();
        ob_end_clean();

        return $scriptContent;
    }

    public function cache($file) {
        if (!file_exists($this->cachePath)) {
            mkdir($this->cachePath, 0744);
        }
        $cached_file = $this->cachePath . str_replace(array('/', '.html'), array('_', ''), $file . '.php');
        if (!$this->cache_enabled || !file_exists($cached_file) || filemtime($cached_file) < filemtime($file)) {
            $code = $this->includeFiles($file);
            $code = $this->compileCode($code);

            file_put_contents($cached_file, '<?php class_exists(\'' . __CLASS__ . '\') or exit; ?>' . PHP_EOL . $code);
        }
        return $cached_file;
    }

    public function clearCache() {
        foreach(glob($this->cachePath . '*') as $file) {
            unlink($file);
        }
    }

     function compileCode($code) {
        $code = $this->compileBlock($code);
        $code = $this->compileYield($code);
        $code = $this->compileEscapedEchos($code);
        $code = $this->compileEchos($code);
        $code = $this->compilePHP($code);
        return $code;
    }

    private function includeFiles($file) {
        $code = file_get_contents($this->templatesPath . $file);
        preg_match_all('/{% ?(extends|include) ?\'?(.*?)\'? ?%}/i', $code, $matches, PREG_SET_ORDER);
        foreach ($matches as $value) {
            $code = str_replace($value[0], $this->includeFiles($value[2]), $code);
        }
        $code = preg_replace('/{% ?(extends|include) ?\'?(.*?)\'? ?%}/i', '', $code);
        return $code;
    }

    private function compilePHP($code) {
        return preg_replace('~\{%\s*(.+?)\s*\%}~is', '<?php $1 ?>', $code);
    }

    public function compileEchos($code) {
        return preg_replace('~\{{\s*(.+?)\s*\}}~is', '<?php echo $1 ?>', $code);
    }

    private function compileEscapedEchos($code) {
        return preg_replace('~\{{{\s*(.+?)\s*\}}}~is', '<?php echo htmlentities($1, ENT_QUOTES, \'UTF-8\') ?>', $code);
    }

    private function compileBlock($code) {
        preg_match_all('/{% ?block ?(.*?) ?%}(.*?){% ?endblock ?%}/is', $code, $matches, PREG_SET_ORDER);
        foreach ($matches as $value) {
            if (!array_key_exists($value[1], $this->blocks)) $this->blocks[$value[1]] = '';
            if (strpos($value[2], '@parent') === false) {
                $this->blocks[$value[1]] = $value[2];
            } else {
                $this->blocks[$value[1]] = str_replace('@parent', $this->blocks[$value[1]], $value[2]);
            }
            $code = str_replace($value[0], '', $code);
        }
        return $code;
    }

    private function compileYield($code) {
        foreach($this->blocks as $block => $value) {
            $code = preg_replace('/{% ?yield ?' . $block . ' ?%}/', $value, $code);
        }
        $code = preg_replace('/{% ?yield ?(.*?) ?%}/i', '', $code);
        return $code;
    }

    private function filterPages($params = [], $limit = 0) {
        $result = [];
        $counter = 0;
        foreach ($this->pages as $page) {
            if (isset($params['folder'])) {
                if ($page['data']['folder'] == $params['folder']) {
                    $draftPage = $page['data']['draft'] ?? false;
                    if ($draftPage) {
                        continue;
                    }
                    $result[] = $page;
                }
            }
            $counter++;
            if ($limit > 0 && $counter == $limit) {
                break;
            }
        }
        if (isset($params['orderBy'])) {
            $this->sortBy($result, $params['orderBy']);
        }
        return $result;
    }

    private function sortBy(&$pages, $item) {
        list($key, $sort) = explode('|',$item);
        if ($pages) {
            if ($sort == 'desc') {
                usort($pages, function ($a, $b) use ($key) {
                    if ($a["data"][$key] instanceof DateTime && $b["data"][$key] instanceof DateTime) {
                        return ($b["data"][$key] > $a["data"][$key]);
                    } else {
                        return strcmp($b["data"][$key], $a["data"][$key]);
                    }
                });
            } elseif ($sort == 'asc') {
                usort($pages, function ($a, $b) use ($key) {
                    if ($a["data"][$key] instanceof DateTime && $b["data"][$key] instanceof DateTime) {
                        return ($b["data"][$key] < $a["data"][$key]);
                    } else {
                        return strcmp($b["data"][$key], $a["data"][$key]);
                    }
                });
            }
        }
    }

}
