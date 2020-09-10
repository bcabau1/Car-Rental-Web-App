<?php

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;

require __DIR__ . '/vendor/autoload.php';

// API
$api = new ApiContext(
    new OAuthTokenCredential(
        'AWBzjnD7xuf7OvNvcdLFasdfasd48zl9-J3qMnlTozWasdf1ri8l_fObdLod8xS9oCmv1kW29aFDasdf',
        'EP9asdfI7FiW0r5EYasdf7Y9SMkSW7fasd6fZM9vlRTwMl_AIxuN9WasdfRuu-4tBQ5uDONgasdfo2cO'
    )
);

$api->setConfig(array(
    'mode' => 'sandbox',
    'log.LogEnabled' => true,
    'log.FileName' => 'logs.log',
    'log.LogLevel' => 'DEBUG',
));

?>