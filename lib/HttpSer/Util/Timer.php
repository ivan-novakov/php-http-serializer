<?php
namespace HttpSer\Util;


class Timer
{

    /**
     * Timers data.
     * 
     * @var array
     */
    protected $_timers = array();


    public function __construct ()
    {}


    public function startTimer ($label)
    {
        $this->_timers[$label] = array(
            'start' => microtime(true)
        );
    }


    public function stopTimer ($label)
    {
        if (! isset($this->_timers[$label]) || ! isset($this->_timers[$label]['start'])) {
            return;
        }
        
        $this->_timers[$label]['stop'] = microtime(true);
        $this->_timers[$label]['time'] = $this->_timers[$label]['stop'] - $this->_timers[$label]['start'];
    }


    public function getTimerTime ($label)
    {
        if (! isset($this->_timers[$label]) || ! isset($this->_timers[$label]['time'])) {
            return false;
        }
        
        return $this->_timers[$label]['time'];
    }
}