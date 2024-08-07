# Fattura Elettronica PHP Library

## Overview

The `Fattura Elettronica PHP Library` provides a client for interacting with the Fattura Elettronica service. It allows you to send invoices and handle incoming webhooks for the Fattura Elettronica API.

- **API Documentation:** [Fattura Elettronica API](https://fatturaelettronica.brokenice.it)
- **Registration and Guide:** [Gestionefatture](https://gestionefatture.brokenice.it)

## Installation

1. **Install Dependencies:**

Ensure you have `GuzzleHTTP` installed. You can include it in your `composer.json` or run:

```bash
composer require guzzlehttp/guzzle
```

2. **Install the Library:**

Install this library by requiring it in your composer.json or by running:

```bash
composer require brokenice/fattura-elettronica
```

## Usage
### Sending an Invoice

To send an invoice, you need to create an instance of the FatturaElettronicaClient and call the sendInvoice method.

### Example:
```php
<?php

require __DIR__ . '/vendor/autoload.php'; // Adjust the path as necessary

use Brokenice\FatturaElettronica\FatturaElettronicaClient;

// Instantiate the client
$fatturaClient = new FatturaElettronicaClient('your_email@example.com', 'your_password', 'your_signature');

try {
    // Call the sendInvoice method with XML content
    $fatturaClient->sendInvoice('<xml>...</xml>');
    echo 'Invoice sent successfully.';
} catch (Exception $e) {
    // Handle exceptions
    echo 'An error occurred: ' . $e->getMessage();
}
```

## Handling Webhooks
To handle incoming webhooks, you need to create a class extending FatturaElettronicaWebhook and implement the received method.

```bash
<?php

namespace Brokenice\Pulse\Http\Controllers\Invoices;

use Brokenice\FatturaElettronica\FatturaElettronicaWebhook as BaseWebhook;
use Brokenice\FatturaElettronica\FileSdI;

class FatturaElettronicaWebhook extends BaseWebhook
{
    public function received(FileSdI $fileSdI): void
    {
        // Handle the received FileSdI
        echo 'File received successfully: ' . $fileSdI;
    }
}
```

## Define a Route (Laravel Example):

```php
use Brokenice\Pulse\Http\Controllers\Invoices\FatturaElettronicaWebhook;

Route::post('/webhook', [FatturaElettronicaWebhook::class, 'handle']);
```

## Class Reference
### FatturaElettronicaClient
- Properties:
  - public string $endPoint - Endpoint URL for the Fattura Elettronica service.
  - public string $email - Email used for authentication.
  - public string $password - Password used for authentication.
  - public string $signature - Signature used for authentication.
- Methods:
  - public function __construct(string $email, string $password, string $signature) - Constructor to initialize credentials.
  - public function sendInvoice(string $xml): void - Sends an invoice in XML format.
  
### FatturaElettronicaWebhook
- Methods:
  - public function handle(): ?bool - Handles incoming webhook requests.
  - public abstract function received(FileSdI $fileSdI): void - Abstract method to process received FileSdI objects.
- FileSdI
  - Properties:
    - public $IdentificativoSdI - Identifier of the SDI.   
  - Methods:
    - public function __construct(\StdClass $parametersIn = null) - Constructor to initialize with parameters.
    - public function __toString() - String representation of the object.

## Error Handling
Exceptions thrown during API requests or webhook processing can be caught and handled as needed. Ensure proper exception handling in production environments to manage errors gracefully.

## License
This library is licensed under the MIT License. See LICENSE for more details.

Feel free to adjust the paths, details, and examples according to your specific setup and requirements. This README should provide a clear and practical guide for users to get started with your library.