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
 * @package     OnceSignal
 */

/**
 * @namespace
 */
namespace Signal;

use \InvalidArgumentException;
use \Closure;

/**
 * OnceSignal is a signal that is only dispatches the associated listeners a
 * single time. After a listener has been dispatched it is removed from the SlotList.
 *
 * @category    Signal
 * @package     OnceSignal
 */
class OnceSignal implements IOnceSignal
{
    protected static $_baseTypes = array('array', 
                                         'bool',
                                         'double',
                                         'float',
                                         'int',
                                         'integer',
                                         'long',
                                         'null',
                                         'numeric',
                                         'object',
                                         'real',
                                         'resource',
                                         'scalar',
                                         'string');
    
    protected $valueClasses;
    protected $slots;
    
    /**
     * Initialize the SlotList and assign any value classes.
     */
    public function __construct()
    {
        $this->removeAll();
        $this->valueClasses(func_get_args());
    }
    
    /**
     * Setter/Getter for adding value classes. These are validated upon dispatch.
     * 
     * @param array $classes
     * @return OnceSignal 
     */
    public function valueClasses(array $classes = null)
    {
        if (null === $classes) {
            return $this->valueClasses;
        }
        $this->valueClasses = $classes;
        
        return $this;
    }
    
    /**
     * Return the number of listeners attached to the signal.
     * 
     * @return int
     */
    public function numListeners()
    {
        return $this->slots->count();
    }
    
    /**
     * Add a listener that is only dispatched once.
     * 
     * @param array|Closure $listener
     * @return ISlot 
     */
    public function addOnce($listener)
    {
        return $this->register($listener, true);
    }
    
    /**
     * Validate the value classes and execute the slot listener. Any number
     * of arguments can be parsed to the dispatch call.
     * 
     * @return OnceSignal 
     */
    public function dispatch()
    {
        $args = func_get_args();
        $argLen = count($args);
        $valueClasses = $this->valueClasses();
        $valueClassLen = count($valueClasses);
        if ($valueClassLen > 0 && $argLen !== $valueClassLen) {
            throw new InvalidArgumentException(
                    sprintf('Value class mismatch, expected %d got %d', 
                            $valueClassLen, $argLen));
        }
        if ($valueClassLen > 0) {
            for ($i = 0; $i < $argLen; $i++) {
                $this->validateValueClass($valueClasses[$i], $args[$i]);
            }
        }
        foreach ($this->slots->getArrayCopy() as $slot) {
            $slot->execute($args);
        }
        
        return $this;
    }
    
    /**
     * Remove a slot from the signal.
     * 
     * @param string $id
     * @return OnceSignal 
     */
    public function remove($id)
    {
        for ($i = 0, $ii = $this->numListeners(); $i < $ii; $i++) {
            $slot = $this->slots->offsetGet($i);
            if ($slot->id() === $id) {
                $this->slots->offsetUnset($i);
            }
        }
        
        return $this;
    }
    
    /**
     * Remove all slots from the signal.
     * 
     * @return OnceSignal 
     */
    public function removeAll()
    {
        $this->slots = new SlotList;
        
        return $this;
    }
    
    /**
     * Validate the value classes based on the base types. Throw an exception
     * if the value class does not match what was specified.
     * 
     * @param string $expected
     * @param mixed $value
     * @return bool 
     */
    protected function validateValueClass($expected, $value)
    {
        $valid = true;
        if (in_array(strtolower($expected), self::$_baseTypes)) {
            $method = 'is_' . $expected;
            if (!$method($value)) {
                $valid = false;
            }
        } else {
            if (!($value instanceof $expected)) {
                $valid = false;
                $value = get_class($value);
            }
        }
        if (false === $valid) {
            throw new InvalidArgumentException(
                        sprintf('Argument does not match required value class, 
                                    expected %s, received %s', 
                                $expected, (string)$value)
                        );
        }
        
        return true;
    }
    
    /**
     * Register a listener on the slot list.
     * 
     * @param array|Closure $listener
     * @param bool $once
     * @return Slot 
     */
    protected function register($listener, $once = false)
    {
        $slot = new Slot($listener, $this, (bool)$once);
        $this->slots->append($slot);
        
        return $slot;
    }
    
    /**
     * Retrieve some basic information about the signal.
     * 
     * @return string 
     */
    public function __toString()
    {
        return sprintf('Signal class: %s, number of listeners: %s', 
                get_class($this), $this->numListeners());
    }
}