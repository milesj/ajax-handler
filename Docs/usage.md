# AjaxHandler #

*Documentation may be outdated or incomplete as some URLs may no longer exist.*

*Warning! This codebase is deprecated and will no longer receive support; excluding critical issues.*

The Ajax Handler is a CakePHP Component that processes and handles AJAX requests. It determines what action should be dealt as an AJAX call, applies the appropriate filters, prepares the data for a response and responds with the appropriate headers.

* Handles pre-defined Controller actions as AJAX
* Formats the AJAX Post/Get into Controller $data values
* Blackholes/Kills the request if the action is not called through AJAX
* Respond with a success or failure message
* Automatically format your data into JSON, XML, HTML or plain text
* Responds with the correct Content-Type headers
* Utilizes the Request Handler

## Installation ##

To use the `AjaxHandler`, add it to your `$components` array in your `Controller`. If you want extra security and validation, apply the `Security` component to your `Controller`.

```php
class AjaxController extends AppController {
    public $components = array('AjaxHandler', 'Security');
}
```

The next step is to tell the component which actions in the `Controller` should be handled as an AJAX call. You can pass the `handle()` method an argument of * to apply to all actions, or a separate list of action names (Similar to how `Auth::allow()` works). By default, the component does not handle any actions.

```php
public function beforeFilter() {
    parent::beforeFilter();

    $this->AjaxHandler->handle('*');
    // Or individual actions
    $this->AjaxHandler->handle('login', 'logout', 'postComment');
}
```

## Response Output ##

You can use only 4 types of content to respond as, they are JSON, XML, HTML and text. By default all content is returned in JSON format, but you can tell which content types to use by passing the type as the first argument for `respond()` (more on this later).

Additionally all JSON and XML returns will have the following values: success, data and code. The success value would be a boolean true/false, if the current action was completed successfully or failed. The data value would contain any messages, errors, objects, arrays or data that you need to use in your scripts. Lastly we have the optional code value, which can be used to determine the severity of messages (example 0-9). Below is an example of each responses output:

```javascript
{
    "success": true,
    "data": "Success!",
    "code": 1
}
```

All array to XML conversions use the `TypeConverter::toXml()` method.

```markup
<?xml version="1.0" encoding="UTF-8" ?>
<root>
    <success>true</success>
    <data>/* Your data */</data>
    <code>1</code>
</root>
```

## Responding ##

While doing your logic in the action, you may want to mark the response as a success or failure and insert the respective data and code. You can do this by using the `response()` method.

```php
// No data required, just a status
$this->AjaxHandler->response(true);

// Return the $data with the JSON
$this->AjaxHandler->response(true, $this->request->data);

// Action failed, apply code
$this->AjaxHandler->response(false, $this->request->data, -1);
```

Once you have set your response, you will need to respond that data back to the client / script. You would use the `respond()` method, which should always be placed at the end of your action to ensure that all logic has completed. The `respond()` method takes a first argument for its content type: json (default), xml, text, html.

The `respond()` method generates the correct HTTP headers, formats and displays the output, so its best to use it!

```php
return $this->AjaxHandler->respond();

// Return as XML
return $this->AjaxHandler->respond('xml');
```

The `response()` method also takes a second argument, an array of values for the status, code and data keys. This is useful for very short code blocks.

```php
return $this->AjaxHandler->respond('xml', array(
    'success' => true,
    'data' => 'Complete!'
));
```

## Example Action ##

Below is an example `Controller` action utilizing the `AjaxHandler`. The action is used for logging in a user.

```php
public function login() {
    $data = $this->request->data;
    $response = array('success' => false);
    
    if (!empty($data['username']) && !empty($data['password'])) {
        if ($this->Auth->login($data)) {
            $response['success'] = true;
            $response['data'] = $data;
        } else {
            $response['data'] = 'Username/password combo incorrect';
            $response['code'] = 0;
        }
    } else {
        $response['data'] = 'No username/password';
        $response['code'] = -1;
    }

    return $this->AjaxHandler->respond('json', $response);
}
```

## Example Javascript ##

Below is an example Javascript function used to trigger an AJAX call. I am using jQuery to call the AJAX and setting the dataType to JSON to return my content. Make sure your dataType is the same as the AJAX response or the response will fail!

```javascript
function login() {
    var data = $("#UserLoginForm").serialize();

    $.ajax({
        type: "POST",
        url: "/ajax/login/",
        data: data,
        dataType: "json",
        success: function (response) {
            if (response.success) {
                // Success!
            } else {
                console.log(response.data, response.code);
            }
        }
    });

    return false;
}
```
