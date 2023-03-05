<?php

namespace MdBlog;

use Exception;

class FileSystem {

    // get all files and sub-folders from a folder
    static function getAllFilesInFolder($dir, $subFolder = false) {
        $list = [];
        // var_dump($dir,glob($dir . '/*')); die;
        foreach(glob($dir . '/*') as $file) {
            if (is_dir($file)) {
                $dirName = basename($file);
                $files = self::getAllFilesInFolder($file, true);
                $list[$dirName] = $files;
            }
            else {
                $list[] = $file;
            }
        }
        return $list;
    }

    // delete all files and sub-folders from a folder
    static function deleteAll($dir, $subFolder = false) {
        foreach(glob($dir . '/*') as $file) {
            if(is_dir($file))
                self::deleteAll($file, true);
            else
                unlink($file);
        }
        if ($subFolder) {
            rmdir($dir);
        }
    }

    static function makeDir($path) {
        @mkdir($path, 0777, true);
    }

    static function copyFile($soure, $target) {
        @copy($soure, $target);
        // if (!is_dir(dirname($target))) {
        //     self::makeDir(dirname($target));
        // }
        // $s = @fopen($origin, 'rb');
        // $d = @fopen($target, 'wb');
        // $result = @stream_copy_to_stream($s, $d);
    }

    static function getFileContent($file) {
        $content = @file_get_contents($file);
        if ($content === false) {
			throw new Exception("Unable to read file '$file'", 400);
		}

		return $content;
    }

    static function writeFile($file, $content) {
        if (!is_dir(dirname($file))) {
            self::makeDir(dirname($file));
        }

        if (@file_put_contents($file, $content) === false) {
            throw new Exception("Unable to write to file '$file'", 400);
        }
        if (!@chmod($file, 0666)) {

            throw new Exception("Unable to chmod file '$file'", 400);
        }
    }
    // copy assets
    static function copyFromTo($source, $target) {
        foreach(glob($source . '/*') as $sourcePath) {
            $targetPath = str_replace($source, $target, $sourcePath);
            if (is_dir($sourcePath)) {
                self::makeDir($targetPath);
                self::copyFromTo($sourcePath, $targetPath);
            }
            else {
                self::copyFile($sourcePath, $targetPath);
            }
        }
    }
}
