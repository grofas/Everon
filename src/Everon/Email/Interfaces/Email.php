<?php
/**
 * This file is part of the Everon framework.
 *
 * (c) Oliwier Ptak <oliwierptak@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Everon\Email\Interfaces;

/**
 * @author Zeger Hoogeboom <zeger_hoogeboom@hotmail.com>
 * @author Oliwier Ptak <oliwierptak@gmail.com>
 */
interface Email
{
    /**
     * @param array  $headers
     */
    function setHeaders(array $headers);

    /**
     * @param mixed $message
     */
    function setMessage($message);

    /**
     * @return mixed
     */
    function getSubject();

    /**
     * @return mixed
     */
    function getMessage();

    /**
     * @return array
     */
    function getHeaders();

    /**
     * @param mixed $subject
     */
    function setSubject($subject);

    /**
     * @param array $attachments
     */
    function setAttachments($attachments);

    /**
     * @return array
     */
    function getAttachments();
}