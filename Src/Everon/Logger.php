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

/**
 * @method \DateTime critical
 * @method \DateTime notFound
 * 
 */
class Logger implements Interfaces\Logger
{
    use Helper\Date;
    
    protected $log_files = [
        'critical' => '500.log',
        'notFound' => '404.log',
    ];
    
    protected $log_directory = null;
    
    protected static $log_guid = null;
    
    protected $enabled = false;
    
    
    public function __construct($directory, $enabled)
    {
        $this->log_directory = $directory;
        $this->enabled = $enabled;
    }
    
    public function setGuid($guid)
    {
        self::$log_guid = $guid;
    }

    /**
     * @param $level
     * @return \SplFileInfo
     */
    protected function getFilenameByLevel($level)
    {
        if (array_key_exists($level, $this->log_files) === false) {
            return new \SplFileInfo($this->getLogDirectory().$level.'.log'); //eg. logs/login.log 
        }
        
        return new \SplFileInfo($this->getLogDirectory().$this->log_files[$level]);
    }

    /**
     * Don't generate errors and only write to log file when possible
     * 
     * @param $message
     * @param $level
     * @param $parameters
     * @return \DateTime
     * @throws Exception\Logger
     */
    protected function write($message, $level, array $parameters)
    {
        if ($this->enabled === false) {
            return null;
        }
        
        if (self::$log_guid === null) {
            throw new Exception\Logger('Guid not set');
        }
        
        if ($message instanceof \Exception) {
            $message = $message->getMessage();
        }
        
        $StarDate = (new \DateTime('@'.time()))->setTimezone($this->getLogDateTimeZone());
        $Filename = $this->getFilenameByLevel($level);
        $Dir = new \SplFileInfo($Filename->getPath());
        
        if ($Dir->isWritable()) {
            if ($Filename->isFile() && $Filename->isWritable() === false) {
                return $StarDate;
            }
            
            $this->logRotate($Filename);
            
            $request_id = substr(self::$log_guid, 0, 6);
            $trace_id =  substr(md5(uniqid()), 0, 6);
            $id = "$request_id/$trace_id";
            
            $message = empty($parameters) === false ? vsprintf($message, $parameters) : $message;
            $message = $StarDate->format($this->getLogDateFormat())." ${id} ".$message;
            $message = $this->oneLiner($message);
            error_log($message."\n", 3, $Filename->getPathname());

            if ($message instanceof \Exception) {
                $this->logTrace($message, $StarDate, $id);
            }
        }
        
        return $StarDate;
    }
    
    protected function logTrace(\Exception $Exception, \DateTime $StarDate, $id)
    {
        $trace = $Exception->getTraceAsString();
        if ($trace !== null) {
            $trace = $StarDate->format($this->getLogDateFormat())." ${id} \n".$trace;
            $Filename = $this->getFilenameByLevel('trace');
            $this->logRotate($Filename);
            error_log($trace."\n", 3, $Filename->getPathname());
        }        
    }
    
    protected function logRotate(\SplFileInfo $Filename)
    {
        if ($Filename->isFile() === false) {
            return;
        }
        
        $size = $Filename->getSize();
        $size = intval($size / 1024);
        
        //reset the log file if its size exceeded 512 KB
        if ($size > 512) { //KB, todo: read it from config
            $h = fopen($Filename->getPathname(), 'w');
            fclose($h);
        }
    }
    
    //todo this 
    protected function oneLiner($lines)
    {
        return str_replace([
            chr(13).chr(10),
            chr(13),
            chr(10),
        ], '|', $lines);
    }
    
    public function getLogDateFormat()
    {
        return 'c';
    }
    
    public function getLogDateTimeZone()
    {
        $timezone = @date_default_timezone_get();
        $timezone = $timezone ?: 'Europe/Amsterdam'; //todo: visit coffeeshop 
        return new \DateTimeZone($timezone);
    }

    public function setLogDirectory($directory)
    {
        $this->log_directory = $directory;
    }

    public function getLogDirectory()
    {
        return $this->log_directory;
    }    
    
    public function setLogFiles(array $files)
    {
        $this->log_files = $files;
    }
    
    public function getLogFiles()
    {
        return $this->log_files;
    }

    public function warn($message, array $parameters=[])
    {
        return $this->write($message, 'warning', $parameters);
    }
    
    public function trace($message, array $parameters=[])
    {
        return $this->write($message, 'trace', $parameters);
    }
    
    public function error($message, array $parameters=[])
    {
        return $this->write($message, 'error', $parameters);
    }
    
    public function debug($message, array $parameters=[])
    {
        return $this->write($message, 'debug', $parameters);
    }

    /**
     * $this->getLogger()->auth(...)  will log to logs/auth.log
     * 
     * @param $name
     * @param $arguments
     * @return \DateTime
     */
    public function __call($name, array $arguments=[])
    {
        $name = escapeshellarg(preg_replace('/[^a-z0-9_]/i', '', $name));
        $name = str_replace(['"', "'"], '', $name);

        @list($message, $parameters) = $arguments;
        $parameters = $parameters ?: [];
        return $this->write($message, $name, $parameters);
    }
    
}