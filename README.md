Introduction
============
Inspired by Robert Penners as3signals.

For more information on signals/slots, visit Robert Penners as3signals github
repo at https://github.com/robertpenner/as3-signals

Usage
============

>$signal = new Signal\Signal();
>$signal->add(function() { echo 'dispatched!'; });
>$signal->dispatch();

>$signal = new Signal\Signal('string');
>$signal->add(function() { echo 'failed, you dispatched a string instead of an int'; });
>$signal->dispatch(1);

More examples to come, as with unit tests.