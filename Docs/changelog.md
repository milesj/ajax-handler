# Changelog #

*These logs may be outdated or incomplete.*

## 2.0.0 ##

* Updated to CakePHP 2.0 (not backwards compatible with 1.3)
* Used the Controller CakeRequest object in place of RequestHandler
* Renamed $__handledActions to $_handled and made protected
* Removed $__responseTypes in favor of CakeResponse
* Removed the data gathering from startup() in favor of CakeRequest
* Removed valid() as that should be done externally

## 1.6 ##

* Made operation comparisons strict
* Removed the 2nd argument of respond()
* Re-added the $this->Controller references

## 1.5 ##

* Changed $allowRemoteRequests to $allowRemote
* Removed $this->Controller references
* response() now takes an array as the 3rd argument
* Updated TypeConverter

## 1.4 ##

* JSON encoding now uses TypeConverter
* Updated TypeConverter version
* Removed PHP 4 argument references

## 1.3 ##

* Updated to CakePHP 1.3
* Added the TypeConverter class to handle the XML to array (and vice versa) conversion process
* Removed the response "message" key

## 1.2 ##

* Added an $allowRemoteRequests property to determine if remote AJAX calls should be allowed
* Moved the debug configuration to only apply for AJAX calls
* Added logic that will add a "messages" parameter to the response if a Flash message is set

## 1.1 ##

* Upgraded to PHP 5 only
* Added more security checking to make sure the AJAX call comes from the same domain
* Added functionality for objects within __format()

## 1.0 ##

* First initial release of the Ajax Handler component
