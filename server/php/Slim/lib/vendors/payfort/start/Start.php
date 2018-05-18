<?php

//File to use when using lib without composer.
require_once dirname(__FILE__) . '/src/Start.php';
require_once dirname(__FILE__) . '/src/Start/Net/Curl.php';
require_once dirname(__FILE__) . '/src/Start/Net/Stream.php';
require_once dirname(__FILE__) . '/src/Start/Request.php';
require_once dirname(__FILE__) . '/src/Start/Charge.php';
require_once dirname(__FILE__) . '/src/Start/Error.php';
require_once dirname(__FILE__) . '/src/Start/Customer.php';
require_once dirname(__FILE__) . '/src/Start/Token.php';
require_once dirname(__FILE__) . '/src/Start/Error/Authentication.php';
require_once dirname(__FILE__) . '/src/Start/Error/Banking.php';
require_once dirname(__FILE__) . '/src/Start/Error/Processing.php';
require_once dirname(__FILE__) . '/src/Start/Error/Request.php';
require_once dirname(__FILE__) . '/src/Start/Error/SSLError.php';
