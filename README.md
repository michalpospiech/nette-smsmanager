# nette-smsmanager
SMSManager API for Nette

# Using
$this->sms->setRecipientNumber('732123456');
$this->sms->setCustomId(28);
$this->sms->setMessage('Lorem ipsum');
$this->sms->setType(\SMSManager\SMS::REQUEST_TYPE_LOW);
$this->sms->createRequest();

$this->sms->send();

# Types SMS
REQUEST_TYPE_LOW: Lowcost SMS
REQUEST_TYPE_HIGH: High Quality SMS
REQUEST_TYPE_DIRECT: Direct SMS
