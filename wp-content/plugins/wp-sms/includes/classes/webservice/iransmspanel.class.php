<?php
	class iransmspanel
	{

		/**
		* Host
		*
		* @var	string
		*/
		private $host = "2972.ir";

		/**
		* URI
		*
		* @var	string
		*/
		private $uri = '/api';

		private $wsdl_link = "http://www.2972.ir/wsdl?XML";

		public $tariff = "http://iransmspanel.ir/?TheAction=viewpage&title=smscharge";
		public $unitrial = false;
		public $unit;
		public $flash = "enable";
		public $user;
		public $pass;
		public $from;
		public $to;
		public $port = 0;
		public $msg;
		public $isflash = false;

		/**
		 * This function is used to send SMS via socket.
		 * 
		 * @param   string      Username
		 * @param   string      Password
		 * @param   string      Number (From - Example: 100002972)
		 * @param   string      Recipient Number
		 * @param   integer     Port Number
		 * @param   string      Message
		 * @param   bool        Is Flash SMS?
		 * @return
		 */
		private function Send_Via_Socket($username, $password, $number, $recipient, $port, $message, $flash) {
			$result = $response = '';
			############################# PARAMETERS #############################
			$params = array(
				'username'  => $username,
				'password'  => $password,
				'number'    => $number,
				'recipient' => $recipient,
				'port'      => $port,
				'message'   => $message,
				'flash'     => $flash
			);
			$parameters = '';
			foreach ($params AS $name => $value) $parameters .= ($parameters != '' ? '&' : '') . "$name=" . urlencode($value);
			######################################################################
			$sockerrno = 0;
			$sockerr = ''; 
			$socket = @fsockopen($this->host, 80, $sockerrno, $sockerr, 2);
			if ($sockerr == '')
			{
				@fputs($socket, "POST $this->uri HTTP/1.1\nHost: $this->host\nContent-type: application/x-www-form-urlencoded\nContent-length: " . strlen($parameters) . "\nConnection: close\n\n$parameters");
				$result = trim(fgets($socket));
				while (!@feof($socket)) $response .= @fread($socket, 256);
				@fclose($socket);
				#################### SPLIT HEADER AND DOCUMENT BODY ##################
				if ($result == 'HTTP/1.1 200 OK')
				{
					$hunks = explode("\r\n\r\n",trim($response));
					if (!is_array($hunks) OR sizeof($hunks) < 2) return false;
					else $response = $hunks[count($hunks) - 1];
					if (preg_match('#(.+)[\r\n](.+)[\r\n](.+)#', $response, $match)) $response = $match[2];
				}
			}
			else return false;
			######################################################################
			return ($result == 'HTTP/1.1 200 OK') ? $response : false;
		}

		/**
		* This function allows class to set post values.
		* 
		* @param	string		Reference to options variable
		* @param	array		Options array
		*
		*/
		private function curl_post_fields(&$options, $fields) {
			$options[CURLOPT_POSTFIELDS] = $fields; 
		}

		/**
		* This function allows class to execute the given url and return result
		* 
		* @param	string		Reference to cURL handle
		* @param	string		URL
		* @param	array		Options for cURL transfer
		* 
		* @return	string
		*
		*/
		private function curl_execute(&$handle, $url, $options = null) {
			if (!is_array($options))
			{
				$options = array();
			}
			else if (in_array(CURLOPT_POSTFIELDS, $options) AND sizeof($options[CURLOPT_POSTFIELDS]) > 0)
			{
				$options[CURLOPT_POST] = true;
			}
			
			$options[CURLOPT_USERAGENT] = 'PHP';
			$options[CURLOPT_RETURNTRANSFER] = true;
			$options[CURLOPT_URL] = $url;
			
			$handle = @curl_init(); // initialize cURL session
			if ($handle AND @is_resource($handle))
			{
				@curl_setopt_array($handle, $options); // set options for cURL transfer 
				$result = @curl_exec($handle); // execute cURL session
				@curl_close($handle); // close cURL session
			}
			else
			{
				$result = false;
			}
			
			return $result;
		}

		/**
		 * This function is used to send SMS via cURL.
		 * 
		 * @param   string      Username
		 * @param   string      Password
		 * @param   string      Number (From - Example: 100002972)
		 * @param   string      Recipient Number
		 * @param   integer     Port Number
		 * @param   string      Message
		 * @param   bool        Is Flash SMS?
		 * @return
		 */
		private function Send_Via_cURL($username, $password, $number, $recipient, $port, $message, $flash) {
			$handle = null;
			$options = array();
			$this->curl_post_fields($options, array(
				'username'  => $username,
				'password'  => $password,
				'number'    => $number,
				'recipient' => $recipient,
				'port'      => $port,
				'message'   => $message,
				'flash'     => $flash
			));
			return $this->curl_execute($handle, "http://www.$this->host{$this->uri}", $options);
		}

		/**
		 * This function is used to send SMS via http://www.2972.ir
		 * 
		 * @param   string      Username
		 * @param   string      Password
		 * @param   string      Number (From - Example: 100002972)
		 * @param   string      Recipient Number
		 * @param   integer     Port Number
		 * @param   string      Message
		 * @param   bool        Is Flash SMS?
		 * @return
		 */
		public function send_sms() {
			if (@function_exists('curl_init')) {
				$this->Send_Via_cURL($this->user, $this->pass, $this->from, implode(',', $this->to), $this->port, $this->msg, $this->isflash);
			}
			
			$result = $this->Send_Via_Socket($this->user, $this->pass, $this->from, implode(',', $this->to), $this->port, $this->msg, $this->isflash);
			
			if(!$result)
				return true;
		}

		public function get_credit() {
		
			$client = new SoapClient($this->wsdl_link);
			
			if($client->Authentication($this->user, $this->pass)) {
				return $client->GetCredit();
			}
		}
	}
?>