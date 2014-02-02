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

use Everon\Interfaces;
use Everon\Exception;
use Everon\DataMapper\Interfaces\ConnectionItem;

interface PdoAdapter
{
    /**
     * @param $sql
     * @param array $parameters
     * @param int $fetch_mode
     * @return \PDOStatement
     * @throws Exception\Pdo
     */
    function execute($sql, array $parameters=null, $fetch_mode=\PDO::FETCH_ASSOC);

    /**
     * @param ConnectionItem $ConnectionConfig
     */
    function setConnectionConfig(ConnectionItem $ConnectionConfig);

    /**
     * @return ConnectionItem
     */
    function getConnectionConfig();

    /**
     * @param \PDO $Pdo
     */
    function setPdo(\PDO $Pdo);

    /**
     * @return \PDO
     */
    function getPdo();
}
