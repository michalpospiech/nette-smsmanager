# nette-smsmanager
SMSManager API for Nette

# Using
$this->sms->setRecipientNumber('732123456');__
$this->sms->setCustomId(28);__
$this->sms->setMessage('Lorem ipsum');__
$this->sms->setType(\SMSManager\SMS::REQUEST_TYPE_LOW);__
$this->sms->createRequest();__
__
$this->sms->send();

# Types SMS
REQUEST_TYPE_LOW: Lowcost SMS__
REQUEST_TYPE_HIGH: High Quality SMS__
REQUEST_TYPE_DIRECT: Direct SMS__
