# Ajax Handler v1.6 #

A CakePHP Component that will automatically handle and render AJAX calls and apply the appropriate returned format and headers.

## Requirements ##

* CakePHP 1.2.x, 1.3.x
* PHP 5.2.x, 5.3.x
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
