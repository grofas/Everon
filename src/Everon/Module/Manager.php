<?php
/**
 * This file is part of the Everon framework.
 *
 * (c) Oliwier Ptak <oliwierptak@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Everon\Module;

use Everon\Dependency;
use Everon\Exception;
use Everon\Helper;
use Everon\Interfaces;

class Manager implements Interfaces\ModuleManager
{
    use Dependency\Injection\ConfigManager;
    use Dependency\Injection\Environment;
    use Dependency\Injection\Factory;
    use Dependency\Injection\FileSystem;

    use Helper\Arrays;
    use Helper\Asserts;
    use Helper\Asserts\IsArrayKey;
    use Helper\IsIterable;

    /**
     * @var array
     */
    protected $modules = null;
    
    protected $configs_were_registered = false;
    
    
    protected function initConfigs()
    {
        if ($this->configs_were_registered) {
            return;
        }
        
        $module_list = $this->getFileSystem()->listPathDir($this->getEnvironment()->getModule());
        /**
         * @var \DirectoryIterator $Dir
         */
        foreach ($module_list as $Dir) {
            $module_name = $Dir->getBasename();
            $this->registerModuleConfigs($module_name);
        }
        
        $this->configs_were_registered = true;
    }

    /**
     * @param $module_name
     */
    protected function registerModuleConfigs($module_name)
    {
        $filename = $this->getFileSystem()->getRealPath('//Module/'.$module_name.'/Config/module.ini');
        $this->getConfigManager()->registerByFilename($module_name.'@'.'module', $filename);
        
        $filename = $this->getFileSystem()->getRealPath('//Module/'.$module_name.'/Config/router.ini');
        $this->getConfigManager()->registerByFilename($module_name.'@'.'router', $filename);
    }

    protected function initModules()
    {
        $module_list = $this->getFileSystem()->listPathDir($this->getEnvironment()->getModule());
        $active_modules = $this->getConfigManager()->getConfigValue('application.modules.active', ['_Core']);
        
        /**
         * @var \DirectoryIterator $Dir
         */
        foreach ($module_list as $Dir) {
            $module_name = $Dir->getBasename();
            if (in_array($module_name, $active_modules) === false) {
                continue;
            }

            if (isset($this->modules[$module_name])) {
                throw new Exception\Module('Module: "%s" is already registered');
            }
            
            $Config = $this->getModuleConfig($module_name, 'module');
            $ConfigRouter = $this->getModuleConfig($module_name, 'router');
            $this->modules[$module_name] = $this->getFactory()->buildModule($module_name, $Dir->getPathname().DIRECTORY_SEPARATOR, $Config, $ConfigRouter);
        }
    }

    /**
     * @param $module_name
     * @param $config_name
     * @return Interfaces\Config
     */
    protected function getModuleConfig($module_name, $config_name)
    {
        $this->initConfigs();
        $name = $module_name.'@'.$config_name;
        return $this->getConfigManager()->getConfigByName($name);
    }

    /**
     * @inheritdoc
     */
    public function getModule($name)
    {
        if ($this->modules === null) {
            $this->initModules();
        }
        
        return $this->modules[$name];
    }

    /**
     * @inheritdoc
     */
    public function getDefaultModule()
    {
        $default_module = $this->getConfigManager()->getConfigValue('application.modules.default', '_Core');
        return $this->getModule($default_module);
    }

}