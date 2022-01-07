<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Session class
*/

            //if your php doesn't support apache_request_headers
            if( !function_exists('apache_request_headers') ) {
                function apache_request_headers() {
                    $arh = array();
                    $rx_http = '/\AHTTP_/';

                    foreach($_SERVER as $key => $val) {
                        if( preg_match($rx_http, $key) ) {
                            $arh_key = preg_replace($rx_http, '', $key);
                            $rx_matches = array();
                       // do some nasty string manipulations to restore the original letter case
                       // this should work in most cases
                            $rx_matches = explode('_', $arh_key);

                            if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
                                foreach($rx_matches as $ak_key => $ak_val) {
                                    $rx_matches[$ak_key] = ucfirst($ak_val);
                                }

                                $arh_key = implode('-', $rx_matches);
                            }

                            $arh[$arh_key] = $val;
                        }
                    }

                    return( $arh );
                }
            }

			    class Session {

			
	protected $adaptor;
	protected $session_id;
	public $data = array();

	/**
	 * Constructor
	 *
	 * @param	string	$adaptor
	 * @param	object	$registry
 	*/
	public function __construct($adaptor, $registry = '') {
		$class = 'Session\\' . $adaptor;
		
		if (class_exists($class)) {
			if ($registry) {
				$this->adaptor = new $class($registry);
			} else {
				$this->adaptor = new $class();
			}	
			
			register_shutdown_function(array($this, 'close'));
		} else {
			trigger_error('Error: Could not load cache adaptor ' . $adaptor . ' session!');
			exit();
		}	
	}
	
	/**
	 * 
	 *
	 * @return	string
 	*/	
	public function getId() {
		return $this->session_id;
	}

	/**
	 *
	 *
	 * @param	string	$session_id
	 *
	 * @return	string
 	*/	
	public function start($session_id = '') {
		

			$headers = apache_request_headers();

			if(isset($headers['X-Oc-Session'])){
				$session_id = $headers['X-Oc-Session'];
			}else if(isset($headers['X-OC-SESSION'])){
				$session_id = $headers['X-OC-SESSION'];
			}else if(isset($headers['x-oc-session'])){
			    $session_id = $headers['x-oc-session'];
			}
            if (!$session_id) {

			
			if (function_exists('random_bytes')) {
				$session_id = substr(bin2hex(random_bytes(26)), 0, 26);
			} else {
				$session_id = substr(bin2hex(openssl_random_pseudo_bytes(26)), 0, 26);
			}
		}

		if (preg_match('/^[a-zA-Z0-9,\-]{22,52}$/', $session_id)) {
			$this->session_id = $session_id;
		} else {
			exit('Error: Invalid session ID!');
		}
		
		$this->data = $this->adaptor->read($session_id);
		
		return $session_id;
	}
	
	/**
	 * 
 	*/
	public function close() {
		$this->adaptor->write($this->session_id, $this->data);
	}
	
	/**
	 * 
 	*/	
	public function __destroy() {
		$this->adaptor->destroy($this->session_id);
	}
}
