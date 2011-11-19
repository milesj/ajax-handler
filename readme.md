# Ajax Handler v1.6 #

A CakePHP Component that will automatically handle and render AJAX calls and apply the appropriate returned format and headers.

This version is only compatible with CakePHP 1.3.

## Compatibility ##

* v1.x - CakePHP 1.3
* v2.x - CakePHP 2.0

## Requirements ##

* PHP 5.2, 5.3
* SimpleXML - http://php.net/manual/book.simplexml.php

## Features ##

* Handles pre-defined Controller actions as AJAX
* Formats the AJAX post/get into Controller $data values
* Blackholes the request if the action is not called through AJAX
* Respond with a success or failure message
* Automatically format your data into JSON, XML, HTML or plain text
* Responds with the correct Content-Type headers
* Utilizes the RequestHandler and TypeConverter

## Documentation ##

Thorough documentation can be found here: http://milesj.me/code/cakephp/ajax-handler
