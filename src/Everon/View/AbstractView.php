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
    use Dependency\Injection\Factory;
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
        return $this->getContainer()->get($name);
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
            $default_action = 'index';
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
    
    public function renderWidget($name)
    {
        $WidgetManager = $this->getViewManager()->getWidgetManager();
        return $WidgetManager->includeWidget($name);
    }
    
    /**
     * @inheritdoc
     */
    public function templetize(array $data)
    {
        return new Helper\PopoProps($data);
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
            $data[$index] = $this->templetize($Item->toArray());
        }

        return $data;
    }
}