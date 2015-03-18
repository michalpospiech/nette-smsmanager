<?php

	namespace SMSManager;

	class SMSHttpException extends \Exception {
		
		public function __construct($code) {
			switch ($code) {
				case 101:
					$error = 'Bad request (missing XMLDATA)';
					break;
				case 102:
					$error = 'Invalid format';
					break;
				case 103:
					$error = 'Invalid username or password';
					break;
				case 104:
					$error = 'Invalid parameter gateway';
					break;
				case 105:
					$error = 'Low credit';
					break;
				case 109:
					$error = 'The requirement does not contain required data';
					break;
				case 201:
					$error = 'No valid phone numbers';
					break;
				case 202:
					$error = 'Text message does not exist or is too long';
					break;
				case 203:
					$error = 'Invalid parameter sender';
					break;
				case 500:
				case 503:
					$error = 'System error';
					break;
				default:
					$error = 'Unkown error code';
			}

			parent::__construct($error, $code);
		}

	}