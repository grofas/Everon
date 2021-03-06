<?php
/**
 * This file is part of the Everon framework.
 *
 * (c) Oliwier Ptak <oliwierptak@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Everon\Rest;

use Everon\Config\Interfaces\ItemRouter;
use Everon\Exception;
use Everon\Router as EveronRouter;

class Router extends EveronRouter implements Interfaces\Router
{
    /**
     * @inheritdoc
     */
    public function getRouteByRequest(\Everon\Interfaces\Request $Request)
    {
        try {
            $DefaultItem = parent::getRouteByRequest($Request);
        }
        catch (\Exception $e) {
            $DefaultItem = null;
        }

        if ($DefaultItem !== null && strcasecmp($Request->getMethod(), $DefaultItem->getMethod()) === 0) {
            return $DefaultItem;
        }
        
        foreach ($this->getConfig()->getItems() as $RouteItem) {
            /**
             * @var ItemRouter $RouteItem
             */
            if ($RouteItem->matchesByPath($Request->getPath())) {
                if ($Request->getMethod() === $RouteItem->getMethod()) {
                    $this->validateAndUpdateRequestAndRouteItem($RouteItem, $Request);
                    return $RouteItem;
                }
            }
        }
        
        throw new Exception\RouteNotDefined($Request->getPath());
    }
}