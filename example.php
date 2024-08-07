<?php

require __DIR__ . '/vendor/autoload.php'; // Adjust the path as necessary

use Brokenice\FatturaElettronica\FatturaElettronicaClient;

// Instantiate your client
$fatturaClient = new FatturaElettronicaClient('test@test.it', 'test', '31231');

try {
    // Call the sendInvoice method
    $fatturaClient->sendInvoice('');
} catch (Exception $e) {
    // Handle exceptions
    echo 'An error occurred: ' . $e->getMessage();
}
