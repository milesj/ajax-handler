<?php
/** 
 * ajax_handler.php
 *
 * A CakePHP Component that will automatically handle and render AJAX calls and apply the appropriate returned format and headers.
 *
 * @author 		Miles Johnson - www.milesj.me
 * @copyright	Copyright 2006-2009, Miles Johnson, Inc.
 * @license 	http://www.opensource.org/licenses/mit-license.php - Licensed under The MIT License
 * @package		AjaxHandler Component
 * @version 	1.2
 * @link		www.milesj.me/resources/script/ajax-handler-component
 */
 
class AjaxHandlerComponent extends Object {

	/**
	 * Current version: www.milesj.me/files/logs/ajax-handler-component
	 * @access public
	 * @var string
	 */
	public $version = '1.2';

	/**
	 * Components
	 * @access public
	 * @var array
	 */
	public $components = array('RequestHandler');

	/**
	 * Should we allow remote AJAX calls
	 * @access public
	 * @var boolean
	 */
	public $allowRemoteRequests = false;
	
	/**
	 * How should XML be formatted: attributes or tags
	 * @access public
	 * @var string
	 */
	public $xmlFormat = 'attributes';

	/**
	 * Determines if the AJAX call was a success or failure
	 * @access protected
	 * @var boolean
	 */
	protected $_success = false;
	
	/**
	 * A user given code associated with failure / success messages
	 * @access protected
	 * @var int
	 */
	protected $_code;
	
	/**
	 * Contains the success messages / errors
	 * @access protected
	 * @var array
	 */
	protected $_data;
	
	/**
	 * Which actions are handled as AJAX
	 * @access protected
	 * @var array
	 */
	private $__handledActions = array();
	
	/**
	 * Types to respond as
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
	 * Load the Controller object
	 * @access public
	 * @param object $Controller
	 * @return void
	 */
	public function initialize(&$Controller) { 
		$this->Controller = $Controller;

		if ($this->RequestHandler->isAjax()) {
			// Turn off debug, dont want to ruin our response
			Configure::write('debug', 0);

			// Must disable security component for AJAX
			if (isset($this->Controller->Security)) {
				$this->Controller->Security->validatePost = false;
			}
			
			// If not from this domain, destroy
			if (($this->allowRemoteRequests === false) && (strpos(env('HTTP_REFERER'), trim(env('HTTP_HOST'), '/')) === false)) {
				if (isset($this->Controller->Security)) {
					$this->Controller->Security->blackHole($this->Controller, 'Invalid referrer detected for this request!');
				} else {
					$this->Controller->redirect(null, 403, true);
				}
			}
		}
	}
	
	/**
	 * Determine if the action is an Ajax action and handle it
	 * @access public
	 * @param object $Controller
	 * @return void
	 */
	public function startup(&$Controller) { 
		$this->Controller = $Controller;
		
		$handled = false;
		if ($this->__handledActions == array('*') || in_array($this->Controller->action, $this->__handledActions)) {
			$handled = true;
		}
		
		if (!$this->RequestHandler->isAjax() && $handled === true) {
			if (isset($this->Controller->Security)) {
				$this->Controller->Security->blackHole($this->Controller, 'You are not authorized to process this request!');
			} else {
				$this->Controller->redirect(null, 401, true);
			}
		}
		
		// Load up the controller with data
		if ($handled === true) {
			if (!empty($this->Controller->params['form'])) {
				$data = $this->Controller->params['form'];
				
			} else if (!empty($this->Controller->params['url'])) {
				$data = $this->Controller->params['url'];
				unset($data['ext'], $data['url']);
			}
			
			if (!empty($data)) {
				$data = array_map('urldecode', $data);
				
				if (!empty($this->Controller->data)) {
					$this->Controller->data = array_merge($this->Controller->data, $data);
				} else {
					$this->Controller->data = $data;
				}
			}
		}
	}
	
	/**
	 * A list of actions that are handled as an AJAX call
	 * @access public
	 * @return void
	 */
	public function handle() {
		$actions = func_get_args();
		
		if ($actions == array('*')) {
			$this->__handledActions = array('*');
		} else if (is_array($actions) && !empty($actions)) {
			$this->__handledActions = array_intersect($actions, get_class_methods($this->Controller));
		}
	}
	
	/**
	 * Respond the AJAX call with the gathered data
	 * @access public
	 * @param string $type
	 * @param boolean $render - Should the view be rendered for HTML
	 * @return mixed
	 */
	public function respond($type = 'json', $render = true) {
		if (!isset($this->__responseTypes[$type])) {
			$type = 'json';
		}
		if (!is_bool($render)) {
			$render = null;
		}
		
		// Set to null, since RH automatically sets AJAX calls to text/html
		$this->RequestHandler->__responseTypeSet = null;
		$this->RequestHandler->respondAs($this->__responseTypes[$type]);
		
		if ($type == 'html') {
			$this->Controller->layout = $this->RequestHandler->ajaxLayout;
			$this->Controller->autoLayout = true;
			$this->Controller->autoRender = $render;
		} else {
			$this->Controller->autoLayout = false;
			$this->Controller->autoRender = false;
		
			echo $this->__format($type);
		}
		
		return;
	}
	
	/**
	 * Handle the response as a success or failure alongside a message or error
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
	 * Makes sure the params passed are clean
	 * @access public
	 * @param string|int $request
	 * @param boolean $isString
	 * @return mixed
	 */
	public function valid($request, $isString = false) {
		if ($isString === false) {
			if (isset($request) && is_numeric($request)) {
				return true;
			}
		} else {
			if (isset($request) && is_string($request) && $request != '') {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * What should happen if the class is called stand alone
	 * @access public
	 * @return mixed
	 */
	public function __toString() {
		return $this->respond();
	}
	
	/**
	 * Format the response into the right content type
	 * @access private
	 * @uses Xml, XmlHelper
	 * @param string $type
	 * @return mixed
	 */
	private function __format($type) {
		switch (strtolower($type)) {
			case 'json':
				$response = array(
					'success' => $this->_success,
					'data' => $this->_data
				);
				
				if (!empty($this->_code)) {
					$response['code'] = $this->_code;
				}

				if (!empty($_SESSION['Message'])) {
					$response['messages'] = $_SESSION['Message'];
				}
				
				$format = json_encode($response);
			break;
			
			case 'xml':
				App::import('Helper', 'Xml');
				$XmlHelper = new XmlHelper();

				$format  = '<?xml version="1.0" encoding="UTF-8"?><root>';
				$format .= '<success>'. $this->_success .'</success>';
				
				if (!empty($this->_code)) {
					$format .= '<code>'. $this->_code .'</code>';
				}
				
				// If object convert to array
				if (is_object($this->_data)) {
					App::import('Core', 'Xml');
					$xml = new Xml($this->_data);
					$this->_data = Set::reverse($xml);
				}

				if (is_string($this->_data) || is_numeric($this->_data)) {
					$data = $this->_data;
					
				} else if (is_array($this->_data)) {
					// Not sure how to determine between multi-dim array or array with Model relations
					if (isset($this->_data[0])) {
						$data = array();
						foreach ($this->_data as $index => $item) {
							$data[] = '<item index="'. $index .'">'. $XmlHelper->serialize($item, array('format' => $this->xmlFormat)) .'</item>';
						}
						$data = implode("\n", $data);
					} else {
						$data = $XmlHelper->serialize($this->_data, array('format' => $this->xmlFormat));
					}
				}

				$format .= '<data>'. $data .'</data>';

				if (!empty($_SESSION['Message'])) {
					$format .= '<messages>'. $XmlHelper->serialize($this->_data, array('format' => $_SESSION['Message'])) .'</messages>';
				}

				$format .= '</root>';
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
