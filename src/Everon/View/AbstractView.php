<?php
/**
 * This file is part of the Everon framework.
 *
 * (c) Oliwier Ptak <oliwierptak@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Everon\View;

use Everon\Helper;
use Everon\Exception;
use Everon\Dependency;
use \Everon\View\Dependency\ViewManager as ViewManagerDependency;

abstract class AbstractView implements Interfaces\View
{
    use Dependency\Injection\ConfigManager;
    use Dependency\Injection\Logger;
    use Dependency\Injection\Factory;
    use Dependency\Injection\Router;
    use ViewManagerDependency;

    use Helper\Arrays;
    use Helper\GetUrl;
    use Helper\IsCallable;
    use Helper\IsIterable;
    use Helper\String\EndsWith;
    use Helper\String\LastTokenToName;
    use Helper\String\StartsWith;

    protected $name = null;

    protected $template_directory = null;

    /**
     * @var Interfaces\Template
     */
    protected $Container = null;

    protected $default_extension = '.php';

    protected $index_executed = false;
    
    protected $resources_js = [];
    
    protected $resources_css = [];


    /**
     * @param $template_directory
     * @param $default_extension
     */
    public function __construct($template_directory, $default_extension=null)
    {
        $this->name = $this->stringLastTokenToName(get_called_class());
        $this->template_directory = $template_directory;
        if ($default_extension !== null) {
            $this->default_extension = $default_extension;
        }
    }

    protected function getToString()
    {
        return (string) $this->getContainer();
    }

    /**
     * @param $name
     * @return \SplFileInfo
     */
    protected function getTemplateFilename($name)
    {
        if ($this->stringEndsWith($name, $this->default_extension) === false) {
            $name .= $this->default_extension;
        }

        return new \SplFileInfo($this->getTemplateDirectory().$name);
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @inheritdoc
     */
    public function getTemplateDirectory()
    {
        return $this->template_directory;
    }

    /**
     * @inheritdoc
     */
    public function setTemplateDirectory($directory)
    {
        $this->template_directory = $directory;
    }

    /**
     * @inheritdoc
     */
    /**
     * @return Interfaces\Template|Interfaces\TemplateContainer
     * @throws \Everon\Exception\View
     */
    public function getContainer()
    {
        if ($this->Container === null) {
            $this->Container = $this->getFactory()->buildTemplateContainer('', []);
        }

        return $this->Container;
    }

    /**
     * @inheritdoc
     */
    /**
     * @param Interfaces\TemplateContainer $Container
     */
    public function setContainer(Interfaces\TemplateContainer $Container)
    {
        $this->Container = $Container;
    }

    /**
     * @inheritdoc
     */
    public function setContainerFromString($value)
    {
        $this->getContainer()->setTemplateContent($value);
    }

    /**
     * @inheritdoc
     */
    public function getTemplate($name, $data)
    {
        if ((new \SplFileInfo($this->getTemplateDirectory()))->isDir() === false) {
            throw new Exception\Template('Template directory does not exists in "%s@%s"', [$this->getName(), $name]);
        }
        
        $Filename = $this->getTemplateFilename($name);
        if ($Filename->isFile() === false) {
            return null;
        }
        
        return $this->getFactory()->buildTemplate($Filename, $data);
    }

    /**
     * @inheritdoc
     */
    public function set($name, $value)
    {
        $this->getContainer()->set($name, $value);
    }

    /**
     * @inheritdoc
     */
    public function get($name, $default=null)
    {
        return $this->getContainer()->get($name, $default);
    }

    /**
     * @inheritdoc
     */
    /**
     * @param $name
     */
    public function delete($name)
    {
        $this->getContainer()->delete($name);
    }

    /**
     * @inheritdoc
     */
    public function setData(array $data)
    {
        $this->getContainer()->setData($data);
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        return $this->getContainer()->getData();
    }

    /**
     * @inheritdoc
     */
    public function setDefaultExtension($extension)
    {
        $this->default_extension = $extension;
    }

    /**
     * @inheritdoc
     */
    public function getDefaultExtension()
    {
        return $this->default_extension;
    }

    /**
     * @inheritdoc
     */
    public function getFilename()
    {
        return new \SplFileInfo($this->getTemplateDirectory().$this->getName().$this->getDefaultExtension());
    }

    /**
     * @inheritdoc
     */
    public function execute($action)
    {
        if ($this->index_executed === false) {
            $default_action = 'setup';
            if (strcasecmp($action, $default_action) !== 0) {
                if ($this->isCallable($this, $default_action)) {
                    $this->{$default_action}();
                }
            }
        }
        $this->index_executed = true;

        if ($this->isCallable($this, $action)) {
            return $this->{$action}();
        }

        return null;
    }

    /**
     * @param $name
     * @return string
     */
    public function renderWidget($name)
    {
        try {
            $WidgetManager = $this->getViewManager()->getWidgetManager();
            return $WidgetManager->includeWidget($name);
        }
        catch (\Exception $e) {
            $this->getLogger()->error($e);
            return '';
        }
    }
    
    /**
     * @inheritdoc
     */
    public function templetize(array $data)
    {
        foreach ($data as $key => $value) {
            if ($this->isIterable($value)) {
                $data[$key] = $this->templetize($value);
            }
        }
        
        return new Helper\PopoProps($data);
    }

    /**
     * @inheritdoc
     */
    public function templetizeArray(array $data)
    {
        foreach ($data as $name => $item) {
            $data[$name] = $this->templetize($item);
        }
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function templetizeArrayable(array $data)
    {
        /**
         * @var \Everon\Interfaces\Arrayable $Item
         */
        foreach ($data as $index => $Item) {
            if ($Item instanceof \Everon\Interfaces\Arrayable) {
                $data[$index] = $this->templetize($Item->toArray());
            }
            else if (is_array($Item)) {
                $data[$index] = $this->templetizeArrayable($Item);
            }
        }

        return $data;
    }

    protected function appendFilePaths($tmp_files, $url)
    {
        if (trim($url) === '' || is_array($tmp_files) === false) {
            return $tmp_files;
        }
        
        array_walk($tmp_files, function(&$item) use ($url){
            $item =  $url.$item;
        });
        
        return $tmp_files;
    }

    protected function includeResources()
    {
        if ($this->getConfigManager()->hasConfig('minify') === false) {
            return;
        }
        
        $Config = $this->getConfigManager()->getConfigByName('minify');
        $url_static = $this->getConfigManager()->getConfigValue('static.config.project_url');
        $url_shared = $this->getConfigManager()->getConfigValue('static.config.shared_url');
        
        foreach ($this->resources_js as $name => $items_to_minify) {
            $files = [];
            foreach ($items_to_minify as $resource_name) {
                $ConfigItem = $Config->getItemByName($resource_name);
                if ($ConfigItem === null) {
                    throw new Exception\View('Invalid minify resource name: "%s"', $resource_name);
                }
                
                $path = $ConfigItem->getValueByName('path');
                $url = $url_static;
                if (substr($path, 0, strlen('//shared')) === '//shared') {
                    $url = $url_shared;
                    $path = str_replace('//shared', '', $path);
                }
                else if (substr($path, 0, strlen('//')) === '//') {
                    $url = '';
                    $path = '';
                }
                
                $tmp_files = $ConfigItem->getValueByName('files');
                $minify = (bool) $ConfigItem->getValueByName('minify');
                if ($minify === false) {
                    $files = array_merge($files, $this->appendFilePaths($tmp_files, $url.$path));
                }
                else {
                    $files = [$url.$path.$Config->getItemByName($resource_name)->getValueByName('filename')];
                }
            }
            $this->set($name, $files);
        }


        foreach ($this->resources_css as $name => $items_to_minify) {
            $files = [];
            foreach ($items_to_minify as $resource_name) {
                $ConfigItem = $Config->getItemByName($resource_name);
                if ($ConfigItem === null) {
                    throw new Exception\View('Invalid minify resource name: "%s"', $resource_name);
                }
                
                $path = $ConfigItem->getValueByName('path');
                $url = $url_static;
                if (substr($path, 0, strlen('//shared')) === '//shared') {
                    $url = $url_shared;
                    $path = str_replace('//shared', '', $path);
                }
                else if (substr($path, 0, strlen('//')) === '//') {
                    $url = '';
                    $path = '';
                }

                $tmp_files = $ConfigItem->getValueByName('files');
                $minify = (bool) $ConfigItem->getValueByName('minify');
                if ($minify === false) {
                    $files = array_merge($files, $this->appendFilePaths($tmp_files, $url.$path));
                }
                else {
                    $files = [$url.$path.$Config->getItemByName($resource_name)->getValueByName('filename')];
                }
            }
            $this->set($name, $files);
        }
    } 
    
    public function setup()
    {
        $this->includeResources();
    }
}