<?php
namespace Everon\Task\Interfaces;

use Everon\Domain;

interface Item 
{
    function execute();
    
    function markAsExecuted();
    
    function markAsFailed();
    
    function markAsPending();
    
    function markAsProcessing();

    /**
     * @param string $status
     */
    function setStatus($status);

    /**
     * @return string
     */
    function getStatus();

    /**
     * @return string
     */
    function getType();

    /**
     * @param string $type
     */
    function setType($type);

    /**
     * @param boolean $result
     */
    function setResult($result);

    /**
     * @return boolean
     */
    function getResult();

    /**
     * @param int $priority
     */
    function setPriority($priority);

    /**
     * @return int
     */
    function getPriority();

    /**
     * @param string $error
     */
    function setError($error);

    /**
     * @return string
     */
    function getError();

    /**
     * @param \Everon\Task\Interfaces\Item $Item
     */
    function setData($Item);

    /**
     * @return \Everon\Task\Interfaces\Item
     */
    function getData();
}