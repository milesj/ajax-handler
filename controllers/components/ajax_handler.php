<?php
/** 
 * Ajax Handler Component
 *
 * A CakePHP Component that will automatically handle and render AJAX calls and apply the appropriate returned format and headers.
 *
 * @author      Miles Johnson - http://milesj.me
 * @copyright   Copyright 2006-2011, Miles Johnson, Inc.
 * @license     http://opensource.org/licenses/mit-license.php - Licensed under The MIT License
 * @link        http://milesj.me/resources/script/ajax-handler-component
 */

App::import(array(
	'type' => 'Vendor',
	'name' => 'TypeConverter',
	'file' => 'TypeConverter.php'
));
 
class AjaxHandlerComponent extends Object {

	/**
	 * Current version: http://milesj.me/resources/logs/ajax-handler-component
	 *
	 * @access public
	 * @var string
	 */
	public $version = '1.5';

	/**
	 * Components.
	 *
	 * @access public
	 * @var array
	 */
	public $components = array('RequestHandler');

	/**
	 * Should we allow remote AJAX calls.
	 *
	 * @access public
	 * @var boolean
	 */
	public $allowRemote = false;

	/**
	 * Determines if the AJAX call was a success or failure.
	 *
	 * @access protected
	 * @var boolean
	 */
	protected $_success = false;

	/**
	 * A user given code associated with failure / success messages.
	 *
	 * @access protected
	 * @var int
	 */
	protected $_code;

	/**
	 * Contains the success messages / errors.
	 *
	 * @access protected
	 * @var array
	 */
	protected $_data;

	/**
	 * Which actions are handled as AJAX.
	 *
	 * @access protected
	 * @var array
	 */
	private $__handledActions = array();

	/**
	 * Types to respond as.
	 *
	 * @access protected
	 * @var array
	 */
	private $__responseTypes = array(
		'json'	=> 'application/json',
		'html'	=> 'text/html',
		'xml'	=> 'text/xml',
		'text'	=> 'text/plain'
	);

	/**
	 * Load the Controller object.
	 *
	 * @access public
	 * @param object $Controller
	 * @return void
	 */
	public function initialize($Controller) {
		if ($this->RequestHandler->isAjax()) {
			if (isset($this->allowRemoteRequests)) {
				$this->allowRemote = $this->allowRemoteRequests;
			}

			// Turn off debug, don't want to ruin our response
			Configure::write('debug', 0);

			// Must disable security component for AJAX
			if (isset($Controller->Security)) {
				$Controller->Security->validatePost = false;
			}

			// If not from this domain, destroy
			if (($this->allowRemote === false) && (strpos(env('HTTP_REFERER'), trim(env('HTTP_HOST'), '/')) === false)) {
				if (isset($Controller->Security)) {
					$Controller->Security->blackHole($Controller, 'Invalid referrer detected for this request!');
				} else {
					$Controller->redirect(null, 403, true);
				}
			}
		}
	}

	/**
	 * Determine if the action is an Ajax action and handle it.
	 *
	 * @access public
	 * @param object $Controller
	 * @return void
	 */
	public function startup($Controller) {
		$handled = false;

		if ($this->__handledActions == array('*') || in_array($Controller->action, $this->__handledActions)) {
			$handled = true;
		}

		if (!$this->RequestHandler->isAjax() && $handled) {
			if (isset($Controller->Security)) {
				$Controller->Security->blackHole($Controller, 'You are not authorized to process this request!');
			} else {
				$Controller->redirect(null, 401, true);
			}
		}

		// Load up the controller with data
		if ($handled) {
			$data = array();

			if (!empty($Controller->params['form'])) {
				$data = $Controller->params['form'] + $data;
			}

			if (!empty($Controller->params['url'])) {
				$data = $Controller->params['url'] + $data;
				unset($data['ext'], $data['url']);
			}

			if (!empty($data)) {
				$data = array_map('urldecode', $data);

				if (!empty($Controller->data)) {
					$Controller->data = $data + $Controller->data;
				} else {
					$Controller->data = $data;
				}
			}
		}
	}

	/**
	 * A list of actions that are handled as an AJAX call.
	 *
	 * @access public
	 * @return void
	 */
	public function handle() {
		$actions = func_get_args();

		if ($actions == array('*')) {
			$this->__handledActions = array('*');

		} else if (is_array($actions) && !empty($actions)) {
			$this->__handledActions = array_unique(array_intersect($actions, get_class_methods($Controller)));
		}
	}

	/**
	 * Respond the AJAX call with the gathered data.
	 *
	 * @access public
	 * @param string $type
	 * @param boolean $render - Should the view be rendered for HTML
	 * @param array $response
	 * @return mixed
	 */
	public function respond($type = 'json', $render = true, $response = array()) {
		if (!isset($this->__responseTypes[$type])) {
			$type = 'json';
		}

		// Apply response
		if (!empty($response)) {
			$response = $response + array(
				'success' => null,
				'data' => '',
				'code' => null
			);

			$this->response($response['success'], $response['data'], $response['code']);
		}

		// Set to null, since RH automatically sets AJAX calls to text/html
		$this->RequestHandler->__responseTypeSet = null;
		$this->RequestHandler->respondAs($this->__responseTypes[$type]);

		if ($type == 'html') {
			$Controller->layout = $this->RequestHandler->ajaxLayout;
			$Controller->autoLayout = true;
			$Controller->autoRender = $render;

		} else {
			$Controller->autoLayout = false;
			$Controller->autoRender = false;

			echo $this->__format($type);
		}

		return;
	}

	/**
	 * Handle the response as a success or failure alongside a message or error.
	 *
	 * @access public
	 * @param boolean $success
	 * @param mixed $data
	 * @param mixed $code
	 * @return void
	 */
	public function response($success, $data = '', $code = null) {
		if (is_bool($success)) {
			$this->_success = $success;
		}

		$this->_data = $data;
		$this->_code = $code;
	}

	/**
	 * Makes sure the params passed are clean.
	 *
	 * @access public
	 * @param string|int $request
	 * @param boolean $isString
	 * @return mixed
	 */
	public function valid($request, $isString = false) {
		if ($isString) {
			return (isset($request) && is_string($request) && $request != '');
		} else {
			return (isset($request) && is_numeric($request));
		}
	}

	/**
	 * What should happen if the class is called stand alone.
	 *
	 * @access public
	 * @return mixed
	 */
	public function __toString() {
		return $this->respond();
	}

	/**
	 * Format the response into the right content type.
	 *
	 * @access private
	 * @param string $type
	 * @return mixed
	 */
	private function __format($type) {
		$response = array(
			'success' => $this->_success,
			'data' => $this->_data
		);

		if (!empty($this->_code)) {
			$response['code'] = $this->_code;
		}

		switch (strtolower($type)) {
			case 'json':
				$format = TypeConverter::toJson($response);
			break;
			case 'xml':
				$format = TypeConverter::toXml($response);
			break;
			case 'html';
			case 'text':
			default:
				$format = $this->_data;
			break;
		}

		return $format;
	}

}
