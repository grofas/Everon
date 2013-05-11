<?php
namespace Everon;

/**
 * @property \Everon\Interfaces\ConfigRouter $Config
 * @method \Everon\Interfaces\ConfigRouter getConfig()
 */
class Router implements Interfaces\Router, Interfaces\Arrayable
{
    use Dependency\Request;
    use Dependency\Config;

    use Helper\Asserts;
    use Helper\Asserts\IsArrayKey;
    use Helper\Regex;

    /**
     * @var Interfaces\ConfigItemRouter
     */
    protected $CurrentRoute = null;


    /**
     * @param Interfaces\Request $Request
     * @param Interfaces\ConfigRouter $Config
     */
    public function __construct(Interfaces\Request $Request, Interfaces\ConfigRouter $Config)
    {
        $this->setRequest($Request);
        $this->setConfig($Config);
    }

    protected function initRoutes()
    {
        $this->setCurrentRoute(
            $this->getRouteItemByRequest($this->getRequest())
        );
    }

    /**
     * @param Interfaces\Request $Request
     * @return Interfaces\ConfigItemRouter|null
     * @throws Exception\PageNotFound
     * @throws Exception\Router
     */
    public function getRouteItemByRequest(Interfaces\Request $Request)
    {
        $RouteItem = null;
        $url_tokens = $Request->getUrlAsTokens();

        if (count($url_tokens) == 0) {//empty url @todo: refactor into method, remove this comment
            $RouteItem = $this->getConfig()->getDefaultRoute();
            $RouteItem = ($RouteItem) ?: $this->getRouteItemByUrl($Request->getUrl());
            
            if (is_null($RouteItem)) {
                $RouteItem = $this->getRouteItemByUrl($Request->getUrl());
            }

            if (is_null($RouteItem)) {
                throw new Exception\Router('Default route does not exist');
            }

            $this->setRequestQueryDataAndValidateRoute($RouteItem, $Request);
            $this->setRequestPostDataAndValidateRoute($RouteItem, $Request);
            return $RouteItem;
        }

        foreach ($this->getConfig()->getRoutes() as $RouteItem) {
            /**
             * @var Interfaces\ConfigItemRouter $RouteItem
             */
            $request_url = $Request->getUrl();
            if ($RouteItem->matchesByUrl($request_url)) {
                $this->setRequestQueryDataAndValidateRoute($RouteItem, $Request);
                $this->setRequestPostDataAndValidateRoute($RouteItem, $Request);
                return $RouteItem;
            }
        }

        throw new Exception\PageNotFound($Request->getLocation());
    }

    /**
     * @param Interfaces\ConfigItemRouter $RouteItem
     * @param Interfaces\Request $Request
     */
    public function setRequestQueryDataAndValidateRoute(Interfaces\ConfigItemRouter $RouteItem, Interfaces\Request $Request)
    {
        $parsed_parameters = $RouteItem->validateQueryAndGet($Request->getUrl(), $Request->getQueryCollection());
        $Request->setQueryCollection($parsed_parameters);

        $this->validateRoute(
            $RouteItem->getName(), 
            (array) $RouteItem->getGetRegex(), 
            $Request->getQueryCollection()
        );
    }

    /**
     * @param Interfaces\ConfigItemRouter $RouteItem
     * @param Interfaces\Request $Request
     */
    public function setRequestPostDataAndValidateRoute(Interfaces\ConfigItemRouter $RouteItem, Interfaces\Request $Request)
    {
        $parsed_parameters = $RouteItem->validatePost($Request->getPostCollection());
        $Request->setPostCollection($parsed_parameters);

        $this->validateRoute(
            $RouteItem->getName(), 
            (array) $RouteItem->getPostRegex(), 
            $Request->getPostCollection()
        );
    }

    /**
     * @param $url
     * @return Interfaces\ConfigItemRouter|null
     */
    public function getRouteItemByUrl($url)
    {
        /**
         * @var $RouteItem Interfaces\ConfigItemRouter
         */
        foreach ($this->getConfig()->getRoutes() as $RouteItem) {
            if (strcasecmp($RouteItem->getUrl(), $url) === 0) {
                return $RouteItem;
            }
        }

        return null;
    }

    /**
     * @param $route_name
     * @param array $route_params
     * @param array $request_params
     * @throws Exception\InvalidRouterParameter
     */
    public function validateRoute($route_name, array $route_params, array $request_params)
    {
        foreach ($route_params as $name => $expression) {
            $this->assertIsArrayKey($name, $request_params, 
                vsprintf('Invalid required parameter: "%s" for route: "%s"', [$name, $route_name]),
                'InvalidRouterParameter'
            );
            
            if (trim($expression) != '') {
                $pattern = $pattern = '@^'.$expression.'$@'; //expression should be validated by ConfigItemRouter, no point doing it again
                if (preg_match($pattern, $request_params[$name]) !== 1) {
                    throw new Exception\InvalidRouterParameter('Required parameter: "%s" not set', $name);
                }
            }
        }
    }

    /**
     * @param $route_name
     * @return Interfaces\ConfigItemRouter
     * @throws Exception\Router
     */
    public function getRouteByName($route_name)
    {
        try {
            return $this->getConfig()->getRouteByName($route_name);
        }
        catch (\Exception $e) {
            throw new Exception\Router($e);
        }
    }

    /**
     * @param Interfaces\ConfigItemRouter $RouteItem
     */
    public function setCurrentRoute(Interfaces\ConfigItemRouter $RouteItem)
    {
        $this->CurrentRoute = $RouteItem;
    }

    /**
     * @return Interfaces\ConfigItemRouter|null
     */
    public function getCurrentRoute()
    {
        if (is_null($this->CurrentRoute)) {
            $this->initRoutes();
        }

        return $this->CurrentRoute;
    }

    /**
     * @return array
     */
    public function toArray()
    {
/*        if (is_null($this->CurrentRoute)) {
            $this->initRoutes();
        }*/

        return $this->getCurrentRoute()->toArray();
    }

}