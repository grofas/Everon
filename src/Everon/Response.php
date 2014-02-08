<?php
/**
 * This file is part of the Everon framework.
 *
 * (c) Oliwier Ptak <oliwierptak@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Everon;

use Everon\Dependency;

class Response implements Interfaces\Response
{
    use Dependency\Logger;
    
    protected $data = null;
    protected $HeaderCollection = null;
    protected $content_type = 'text/html';
    protected $charset = 'utf-8';
    protected $status = 200;
    protected $status_message = 'OK';

    protected $result = false;
    protected $guid = null;

    
    public function __construct($guid, Interfaces\Collection $Headers)
    {
        $this->guid = $guid;
        $this->HeaderCollection = $Headers;
    }
    
    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getContentType()
    {
        return $this->content_type;
    }

    public function setContentType($content_type)
    {
        $this->content_type = strtolower($content_type);
    }
    
    public function getCharset()
    {
        return $this->charset;
    }

    public function setCharset($charset)
    {
        $this->charset = $charset;
    }

    public function getStatus()
    {
        return $this->status;
    }
    
    public function getStatusMessage()
    {
        return $this->status_message;
    }

    /**
     * @param string $status_message
     */
    public function setStatusMessage($status_message)
    {
        $this->status_message = $status_message;
    }

    public function setStatus($status)
    {
        $this->status = (int) $status;
    }
    
    public function getResult()
    {
        return $this->result;
    }

    public function setResult($result)
    {
        $this->result = (bool) $result;
    }
    
    protected function sendHeaders()
    {
        if ($this->HeaderCollection->has('content-type') === false) {
            switch ($this->getContentType()) {
                case 'application/json':
                    $this->HeaderCollection->set('content-type', 'application/json');
                    break;

                case 'text/html':
                    $this->HeaderCollection->set('content-type', 'text/html; charset="'.$this->getCharset().'"');
                    break;
                
                case 'text/plain':
                    $this->HeaderCollection->set('content-type', 'text/plain; charset="'.$this->getCharset().'"');
                    break;
            }
        }
        
        header('HTTP/1.1 '.$this->status);
        header('EVRID:'. $this->guid);
        foreach ($this->HeaderCollection as $name => $value) {
            header($name.': '.$value, false);
        }
    }
    
    /**
     * @return Http\HeaderCollection
     */
    public function getHeaderCollection()
    {
        return $this->HeaderCollection;
    }

    public function toHtml()
    {
        $this->setContentType('text/html');
        $this->send();
        return (string) $this->data;
    }

    public function toJson($root='data')
    {
        $this->setContentType('application/json');
        $this->send();
        return json_encode([$root=>$this->data]);
    }
    
    public function toText()
    {
        $this->setContentType('text/plain');
        $this->send();
        return (string) $this->data;
    }
    
    public function send()
    {
        if (headers_sent() === false) {
            $this->sendHeaders();
        }
    }

    public function addHeader($name, $value)
    {
        $this->HeaderCollection->set($name, $value);
    }
    
    public function getHeader($name)
    {
        $this->HeaderCollection->get($name);
    }

}