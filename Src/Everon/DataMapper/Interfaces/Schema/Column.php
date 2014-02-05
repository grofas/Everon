<?php
/**
 * This file is part of the Everon framework.
 *
 * (c) Oliwier Ptak <oliwierptak@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Everon\DataMapper\Interfaces\Schema;

use Everon\Interfaces\Arrayable;
use Everon\Interfaces\Immutable;

interface Column extends Arrayable, Immutable
{
    function isPk();
    function isAutoIncremental();
    function getName();
    function getType();
    function getLength();
    function isNullable();
    function getDefault();
    function getSchema();
    
    /**
     * @return array
     */
    function getValidationRules();
}