<?php
/**
 * This file is part of the Everon framework.
 *
 * (c) Oliwier Ptak <oliwierptak@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Everon;

use Everon\Interfaces;
use Everon\Exception;

class FileSystem implements Interfaces\FileSystem
{
    protected $root = null;
    
    public function __construct($root)
    {
        $this->root = $root;
    }
    
    protected function getRelativePath($path)
    {
        $is_absolute = mb_strtolower($this->root) === mb_strtolower(mb_substr($path, 0, mb_strlen($this->root)));
        if ($path[0] === DIRECTORY_SEPARATOR && $path[1] === DIRECTORY_SEPARATOR) { //eg. '//Tests/Everon/tmp/'
            //strip virtual root
            $path = mb_substr($path, 2, mb_strlen($path));
        }
        else if ($is_absolute) { //absolute, eg. '/var/www/Everon/Tests/Everon/tmp/';
            //strip absolute root from path
            $path = mb_substr($path, mb_strlen($this->root));
        }        
        
        $path = $this->root.$path;
        return $path;
    }
    
    public function getRoot()
    {
        return $this->root;
    }
    
    public function createPath($path, $mode=0775)
    {
        try {
            $path = $this->getRelativePath($path);
            mkdir($path, $mode, true);
        }
        catch (\Exception $e) {
            throw new Exception\FileSystem($e);
        }
    }
    
    public function deletePath($path)
    {
        $path = $this->getRelativePath($path);
        try {
            if (is_dir($path)) {
                $It = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST
                );

                array_map('unlink', iterator_to_array($It));
                rmdir($path);
            }
        }
        catch (\Exception $e) {
            throw new Exception\FileSystem($e);
        }
    }
    
    public function listPath($path)
    {
        $result = [];
        $path = $this->getRelativePath($path);
        $Files = new \GlobIterator($path.DIRECTORY_SEPARATOR.'*.*');
        
        foreach ($Files as $filename => $File) {
            $result[] = $File;
        }
        
        return $result;
    }
    
    public function save($filename, $content)
    {
        $filename = $this->getRelativePath($filename);
        $Filename = new \SplFileInfo($filename);
        
        try {
            $this->createPath($Filename->getPath());
            $h = fopen($Filename->getPathname(), 'w');
            fwrite($h, $content);
            fclose($h);
        }
        catch (\Exception $e) {
            throw new Exception\FileSystem($e);
        }
    }
    
    public function load($filename)
    {
        $filename = $this->getRelativePath($filename);
        try {
            return file_get_contents($filename);
        }
        catch (\Exception $e) {
            throw new Exception\FileSystem($e);
        }
    }
    
    public function delete($filename)
    {
        $filename = $this->getRelativePath($filename);
        try {
            unlink($filename);
        }
        catch (\Exception $e) {
            throw new Exception\FileSystem($e);
        }
    }
}