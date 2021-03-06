<?php
/**
 * This file is part of the Everon framework.
 *
 * (c) Oliwier Ptak <oliwierptak@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Everon\Config;

use Everon\Dependency;
use Everon\Exception;
use Everon\Helper;

class Loader implements Interfaces\Loader
{
    use Dependency\Injection\Factory;
    use Dependency\Injection\FileSystem;
    use Helper\Arrays;

    /**
     * @var string
     */
    protected $config_directory = null;

    /**
     * @var string
     */
    protected $config_flavour_directory = null;
    

    /**
     * @param $config_directory
     * @param $config_flavour_directory
     */
    public function __construct($config_directory, $config_flavour_directory)
    {
        $this->config_directory = $config_directory;
        $this->config_flavour_directory = $config_flavour_directory;
    }

    /**
     * @inheritdoc
     */
    public function getConfigDirectory()
    {
        return $this->config_directory;
    }

    /**
     * @inheritdoc
     */
    public function setConfigDirectory($config_directory)
    {
        $this->config_directory = $config_directory;
    }

    /**
     * @inheritdoc
     */
    public function setConfigFlavourDirectory($config_flavour_directory)
    {
        $this->config_flavour_directory = $config_flavour_directory;
    }

    /**
     * @inheritdoc
     */
    public function getConfigFlavourDirectory()
    {
        return $this->config_flavour_directory;
    }
    
    /**
     * @inheritdoc
     */
    public function readIni($filename)
    {
        return @parse_ini_file($filename, true);
    }

    /**
     * @inheritdoc
     */
    public function load()
    {
        /**
         * @var \SplFileInfo $ConfigFile
         * @var \Closure $Compiler
         */
        $list = $this->loadFromDirectory($this->getConfigDirectory()) ?: [];
        $list_flavour = $this->loadFromDirectory($this->getConfigFlavourDirectory()) ?: [];
        
        return array_merge($list, $list_flavour);
    }

    /**
     * @param $directory
     * @return array
     */
    public function loadFromDirectory($directory)
    {
        /**
         * @var \SplFileInfo $ConfigFile
         * @var \Closure $Compiler
         */
        $list = [];
        $IniFiles = new \GlobIterator($directory.'*.ini');
        foreach ($IniFiles as $config_filename => $ConfigFile) {
            $name = $ConfigFile->getBasename('.ini');
            $list[$name] = $this->loadFromFile($ConfigFile);
        }

        return $list;   
    }
    
    /**
     * @inheritdoc
     */
    public function loadFromFile(\SplFileInfo $ConfigFile)
    {
        $ini_config_data =  $this->readIni($ConfigFile->getPathname());
        
        if (is_array($ini_config_data) === false) {
            throw new Exception\Config('Config data not found for: "%s"', $ConfigFile->getBasename());
        }

        return [
            'filename' => $ConfigFile->getPathname(), 
            'data' => $ini_config_data
        ];
    }

}