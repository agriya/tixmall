[![Build Status](https://travis-ci.org/payfort/start-php.svg?branch=master)](https://travis-ci.org/payfort/start-php)

# Start PHP

Start makes accepting payments in the Middle East ridiculously easy. Sign up for an account at [https://start.payfort.com](https://start.payfort.com).


## Getting Started

Using Start with your PHP project is simple.


### Install via Composer
If you're using [Composer](https://getcomposer.org/doc/00-intro.md#installation-nix) (and really, who isn't these days amirite?), you can simply run:

```bash
php composer.phar require payfort/start
```

.. or add a line to your `composer.json` file:

```json
{
    "require": {
        "payfort/start": "*"
    }
}
```

Now, running `php composer.phar install` will pull the library directly to your local `vendor` folder.

### Install Manually

Get source code of the latest release from github repository: https://github.com/payfort/start-php/releases/latest and copy it to your project.

Inside your php file add this:

```
<?php

require_once("path-to-start-php/Start.php");

?>
```

**Note for Windows:** Before you start development with start-php, please check that your php_curl can work with our ssl certificate (TLSv1.2). You can do this by running unit tests. If you see "SSL connection error" it means that you need to install a new php version (at least 5.5.19).

**Note:** If you're running on a shared host, then you may need to set the `allow_url_fopen` flag for the `php` commands. For the install command, for example, this would look like `php -d allow_url_fopen=On composer.phar install`. The `-d` overrides the `php.ini` settings, where `allow_url_fopen` is usually set to `Off`.

## Using Start

You'll need an account with Start if you don't already have one (grab one real quick at [start.payfort.com](https://start.payfort.com) and come right back .. we'll wait).

Got an account? Great .. let's do this.

### 1. Initializing Start

To get started, you'll need to initialize Start with your secret API key. Here's how that looks (fear not .. we're using a test key, so no real money will be exchanging hands):

```php
require_once('vendor/autoload.php'); # At the top of your PHP file

# Initialize Start object
Start::setApiKey('test_sec_k_25dd497d7e657bb761ad6');
```

That's it! You probably want to do something with the Start object though -- it gets really bored when it doesn't have anything to do. 

Let's run a transaction, shall we.

### 2. Processing a transaction through Start

Now, for the fun part. Here's all the code you need to process a transaction with Start:

```php
Start_Charge::create(array(
  "amount" => 10500, // AED 105.00
  "currency" => "aed",
  "card" => array(
    "number" => "4242424242424242",
    "exp_month" => 11,
    "exp_year" => 2016,
    "cvc" => "123"
  ),
  "description" => "Charge for test@example.com"
));
```

This transaction should be successful since we used the `4242 4242 4242 4242` test credit card. For a complete list of test cards, and their expected output you can check out this link [here](https://whitepayments.com/docs/testing/).

How can you tell that it was successful? Well, if no exception is raised then you're in the clear.

### 3. Handling Errors

Any errors that may occur during a transaction is raised as an Exception. Here's an example of how you can handle errors with Start:

```php
try {
  // Use Start's bindings...
} catch(Start_Error_Banking $e) {
  // Since it's a decline, Start_Error_Banking will be caught
  print('Status is:' . $e->getHttpStatus() . "\n");
  print('Code is:' . $e->getErrorCode() . "\n");
  print('Message is:' . $e->getMessage() . "\n");

} catch (Start_Error_Request $e) {
  // Invalid parameters were supplied to Start's API

} catch (Start_Error_Authentication $e) {
  // There's a problem with that API key you provided

} catch (Start_Error $e) {
  // Display a very generic error to the user, and maybe send
  // yourself an email

} catch (Exception $e) {
  // Something else happened, completely unrelated to Start
  
}
```

## Testing Start

It's probably a good idea to run the unit tests to make sure that everything is fine and dandy. That's also simple.. just run this command from the root of your project folder:

```bash
php vendor/bin/phpunit tests --bootstrap vendor/autoload.php
```

**Note:** you'll need to pull the development dependencies as well, using `composer update --dev` in order to run the test suites.

## Contributing

Read our [Contributing Guidelines](CONTRIBUTING.md) for details

Copyright (c) Payfort.
