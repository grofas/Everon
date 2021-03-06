<?php
/**
 * This file is part of the Everon framework.
 *
 * (c) Oliwier Ptak <oliwierptak@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Everon\Interfaces;

interface ClassMap
{
    /**
     * @inheritdoc
     */
    function addToMap($class, $file);
    
    function loadMap();

    /**
     * @param $class
     * @return null
     */
    function getFilenameFromMap($class);
}
