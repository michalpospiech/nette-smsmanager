<?php

	/**
	 * SMSManager for Nette
	 *
	 * @author		Michal Pospiech
	 * @copyright	Copyright (c) 2015 Michal Pospiech
	 * @license 	MIT License
	 */
	
	namespace SMSManager;

	use \Nette,
		\Nette\Object,
		\Nette\DateTime,
		\Nette\Utils\Validators,
		\Nette\Utils\Strings,
		\SimpleXMLElement;

	class SMS extends Object {

		const XML_URL_SEND = 'xml-api.smsmanager.cz/Send';
		//const XML_URL_SEND = 'stroll-obuv_cz.localhost/cron.php?f=test';
		const HTTP_URL_SEND = 'http-api.smsmanager.cz/Send';
		const HTTP_URL_REQUESTLIST = 'http-api.smsmanager.cz/RequestList';
		const HTTP_URL_REQUESTSTATUS = 'http-api.smsmanager.cz/RequestStatus';
		const HTTP_USER_INFO = 'http-api.smsmanager.cz/GetUserInfo';
		const HTTP_GET_PRICE = 'http-api.smsmanager.cz/GetPrice';

		const REQUEST_TYPE_LOW = 'lowcost';
		const REQUEST_TYPE_HIGH = 'high';
		const REQUEST_TYPE_DIRECT = 'direct';
		const REQUEST_DEFAULT_TYPE = self::REQUEST_TYPE_HIGH;

		public $user;
		public $pass;
		public $ssl = false;

		public $sender;
		public $recipientNumbers = array();
		public $type = self::REQUEST_DEFAULT_TYPE;
		public $message;
		public $customId;
		public $time;
		public $expiration;

		public $requests = array();

		public function __construct($user, $pass, $passIsHash = true, $ssl = false) {
			$this->user = $user;
			$this->pass = !$passIsHash ? SHA1($pass) : $pass;
			$this->ssl = $ssl;
		}

		public function getRequestsTypes($associative = true) {
			$types = array(
				self::REQUEST_TYPE_LOW => 'Lowcost SMS',
				self::REQUEST_TYPE_HIGH => 'High Quality SMS',
				self::REQUEST_TYPE_DIRECT => 'Direct SMS',
			);

			if (!$associative) {
				return array_keys($types);
			}

			return $types;
		}

		public function setSender($sender) {
			if (Validators::is($sender, 'numericint') && !preg_match('/^\d{1,10}$/', $sender)) {
				throw new SMSException('Invalid sender number');
			} else if (Validators::is($sender, 'string:1..11')) {
				throw new SMSException('Invalid sender text');
			} else if (!Validators::is($sender, 'numericint|string:1..11')) {
				throw new SMSException('Invalid sender format');
			}

			$this->sender = $sender;
		}

		public function setRecipientNumber($number) {
			if (Validators::is($number, 'numericint') && !Validators::is($number, 'string:9..18')) {
				throw new SMSException('Invalid recipient number');
			} else if (!Validators::is($number, 'numericint') && !Validators::is(preg_replace('/\+/', '', $number), 'numericint')) {
				throw new SMSException('Invalid recipient number format');
			}

			$this->recipientNumbers[] = $number;
		}

		public function setRecipientNumbers(array $numbers) {
			if (!Validators::is($numbers, 'array')) {
				throw new SMSException('Invalid numbers format');
			}

			foreach ($numbers as $number) {
				$this->setRecipientNumber($number);
			}
		}

		public function setType($type) {
			if (!in_array($type, $this->getRequestsTypes(false))) {
				throw new SMSException('Invalid type format');
			}

			$this->type = $type;
		}

		public function setCustomId($customId) {
			if (!Validators::is($customId, 'integer') || !preg_match('/^\d{1,10}$/', $customId)) {
				throw new SMSException('Invalid custom ID. Custom ID must be integer.');
			}

			$this->customId = $customId;
		}

		public function setTime($time) {
			if (!($time instanceof DateTime) && !($time instanceof \DateTime) && !preg_match('/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}T[0-9]{2}\:[0-9]{2}\:[0-9]{2}$/', $time)) {
				throw new SMSException('Invalid time format');
			} else if ($time instanceof DateTime || $time instanceof \DateTime) {
				$time = $time->format('c');
			}

			$this->time = $time;
		}

		public function setExpiration($expirationTime) {
			if (!($expirationTime instanceof DateTime) && !($expirationTime instanceof \DateTime) && !preg_match('/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}T[0-9]{2}\:[0-9]{2}\:[0-9]{2}$/', $time)) {
				throw new SMSException('Invalid expiration time format');
			} else if ($expirationTime instanceof DateTime || $time instanceof \DateTime) {
				$expirationTime = $expirationTime->format('c');
			}

			$this->expiration = $expirationTime;
		}

		public function setMessage($message, $toAscii = true) {
			if (!Validators::is($message, 'string:1..')) {
				throw new SMSException('Invalid message format');
			}

			$this->message = $toAscii ? Strings::toAscii($message) : $message;
		}

		public function createRequest() {
			$requestData = array(
				'sender' => $this->sender,
				'recipientNumbers' => $this->recipientNumbers,
				'type' => $this->type,
				'message' => $this->message,
				'customId' => $this->customId,
				'time' => $this->time,
				'expiration' => $this->expiration,
			);

			if (!$requestData['recipientNumbers']) {
				throw new SMSException('Recipient numbers is empty');
			} else if (!$requestData['type']) {
				$requestData['type'] = self::REQUEST_DEFAULT_TYPE;
			} else if (!$requestData['message']) {
				throw new SMSException('Message is empty');
			}

			$this->recipientNumbers = null;
			$this->type = self::REQUEST_DEFAULT_TYPE;
			$this->message = null;
			$this->customId = null;
			$this->time = null;
			$this->expiration = null;

			$this->requests[] = $requestData;
		}

		public function send() {
			if (!$this->requests) {
				throw new SMSException('There was no requests');
			}

			$xml = new SimpleXMLElement('<RequestDocument></RequestDocument>');

			$header = $xml->addChild('RequestHeader');
			$header->addChild('Username', $this->user);
			$header->addChild('Password', $this->pass);

			$requestList = $xml->addChild('RequestList');
			foreach ($this->requests as $req) {
				$request = $requestList->addChild('Request');
				$request->addAttribute('Type', $req['type']);
				if ($req['customId']) {
					$request->addAttribute('CustomID', $req['customId']);
				}
				if ($req['sender']) {
					$request->addAttribute('Sender', $req['sender']);
				}
				if ($req['time']) {
					$request->addAttribute('Time', $req['time']);
				}
				if ($req['expiration']) {
					$request->addAttribute('Expiration', $req['expiration']);
				}

				$request->addChild('Message', $req['message']);

				$numbersList = $request->addChild('NumbersList');
				foreach ($req['recipientNumbers'] as $number) {
					$numbersList->addChild('Number', $number);
				}
			}

			return $this->sendXmlRequest($xml->asXML());
		}

		private function sendXmlRequest($xml) {
			$url = ($this->ssl ? 'https://' : 'http://') . self::XML_URL_SEND;
			$conn = curl_init($url);
			curl_setopt($conn, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($conn, CURLOPT_HEADER, false);
			curl_setopt($conn, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($conn, CURLOPT_ENCODING, '');
			curl_setopt($conn, CURLOPT_AUTOREFERER, true);
			curl_setopt($conn, CURLOPT_CONNECTTIMEOUT, 60);
			curl_setopt($conn, CURLOPT_TIMEOUT, 30);
			curl_setopt($conn, CURLOPT_MAXREDIRS, 4);
			curl_setopt($conn, CURLOPT_USERAGENT, 'nettesmsmanager');
			curl_setopt($conn, CURLOPT_POST, 1);
			curl_setopt($conn, CURLOPT_POSTFIELDS, http_build_query(array('XMLDATA' => $xml)));

			$response = curl_exec($conn);
			$header = curl_getinfo($conn);
			$httpCode = curl_getinfo($conn, CURLINFO_HTTP_CODE);
			curl_close($conn);

			if ($httpCode != 200) {
				throw new SMSHttpException(self::getErrorCodeFromResponse($response));
			}

			return self::getResponseData($response);
		}

		private static function getResponseData($response) {
			$xml = new \SimpleXMLElement($response);

			$requests = array();
			foreach ($xml->ResponseRequestList->ResponseRequest as $request) {
				$data = array(
					'RequestID' => (int) $request->RequestID,
					'SmsCount' => (int) $request['SmsCount'],
					'SmsPrice' => (float) $request['SmsPrice'],
					'CustomID' => (int) $request->CustomID,
					'Status' => (int) $xml->Response['ID'],
					'NumbersList' => array(),
				);

				foreach ($request->ResponseNumbersList->Number as $number) {
					$data['NumbersList'][] = (string) $number;
				}

				$requests[$data['RequestID']] = $data;
			}

			return $requests;
		}

		private static function getErrorCodeFromResponse($response) {
			$xml = new \SimpleXMLElement($response);

			if (!isset($xml->Response) || !isset($xml->Response['ID'])) {
				return false;
			}

			if (!isset($xml->Response['Type']) || (string) $xml->Response['Type'] !== 'ERROR') {
				return false;
			}

			return (int) $xml->Response['ID'];
		}

	}