<?php
/**
 * Copyright (c) 2011 Simon Coulton
 * Permission is hereby granted, free of charge, to any person obtaining a copy 
 * of this software and associated documentation files (the "Software"), to deal 
 * in the Software without restriction, including without limitation the rights 
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell 
 * copies of the Software, and to permit persons to whom the Software is 
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR 
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, 
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN 
 * THE SOFTWARE.
 * 
 * @author      Simon Coulton <@simoncoulton>
 * @category    Signal
 * @package     Slot
 */

/**
 * @namespace
 */
namespace Signal;

use \Closure;
use \InvalidArgumentException;

/**
 * The Slot object is responsible for executing each listener when it is 
 * dispatched from the signal.
 *
 * @category    Signal
 * @package     Slot
 */
class Slot implements ISlot
{
    private $_enabled;
    private $_id;
    private $_listener;
    private $_params;
    private $_priority;
    private $_once;
    private $_signal;
    
    /**
     * Initialize the slot.
     * 
     * @param Closure|array $listener
     * @param IOnceSignal $signal
     * @param bool $once
     * @param int $priority 
     */
    public function __construct($listener, IOnceSignal &$signal, 
                                $once = false, $priority = 0)
    {
        if (!($listener instanceof Closure) && !is_array($listener)) {
            throw new InvalidArgumentException(
                    sprintf('Invalid listener for signal %s, expected %s', 
                            (string)$signal, 'Closure|Array')
                    );
        }
        $this->_id = spl_object_hash($this);
        $this->_once = (bool)$once;
        $this->_signal = $signal;
        $this->_params = array();
        $this->enabled();
        $this->listener($listener);
    }
    
    /**
     * Retrieves the id of the slot.
     * 
     * @return mixed 
     */
    public function id()
    {
        return $this->_id;
    }
    
    /**
     * Setter/Getter for the listener.
     * 
     * @param Closure|array $listener
     * @return Closure|Slot|array
     */
    public function listener($listener = null)
    {
        if (null === $listener) {
            return $this->_listener;
        }
        $this->_listener = $listener;
        
        return $this;
    }
    
    /**
     * Sets the params that the slot is to be dispatched with.
     * 
     * @param array $value
     * @return Slot 
     */
    public function params(array $value = null)
    {
        if (null === $value) {
            return $this->_params;
        }
        $this->_params = $value;
        
        return $this;
    }
    
    /**
     * Returns whether or not the slot is only to be triggered a single time.
     * 
     * @return bool
     */
    public function once()
    {
        return $this->_once;
    }
    
    /**
     * Returns the priority of the signal.
     * 
     * @return int 
     */
    public function priority()
    {
        return $this->_priority;
    }
    
    /**
     * Setter/Getter for enabling the slot.
     * 
     * @param bool $value
     * @return bool|Slot 
     */
    public function enabled($value = true)
    {
        if ($this->_enabled === $value) {
            return (bool)$this->_enabled;
        }
        $this->_enabled = $value;
        
        return $this;
    }
    
    /**
     * Executes a listener with any arguments that have been specified in the
     * Signal::dispatch() call.
     * 
     * @param array $args
     * @return Slot 
     */
    public function execute(array $args = array())
    {
        if (!$this->enabled()) {
            return;
        }
        if ($this->once()) {
            $this->remove();
        }
        $args = array_merge($args, $this->params());
        call_user_func_array($this->listener(), $args);
        
        return $this;
    }
    
    /**
     * Remove the slot from the signal it is attached to.
     * 
     * @return Slot
     */
    public function remove()
    {
        $this->_signal->remove($this->id());
        
        return $this;
    }
}