<?php
/**
 * This file is part of the Everon framework.
 *
 * (c) Oliwier Ptak <oliwierptak@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Everon\Http\Message;

class OkNoContent extends AbstractMessage
{
    protected $http_status_code = 204;
    protected $http_message = 'NO CONTENT';
}