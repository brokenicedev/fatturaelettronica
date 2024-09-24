<?php

namespace Brokenice\FatturaElettronica;

use Exception;

abstract class FatturaElettronicaWebhook
{

    /**
     * @return string|bool
     * @throws Exception
     */
    public function handle(): ?bool
    {
        $headerData = getallheaders();
        // Retrieve raw POST data from php://input
        $input = file_get_contents('php://input');

        $validator = new DefaultSignatureValidator();

        try {
            $isValid = $validator->isValid($headerData['Signature'], $this->signatureSecret(), $input);
            if (!$isValid) {
                throw new Exception('Invalid signature');
            }

            // Decode JSON payload if applicable
            $data = json_decode($input, true);

            // If type is missing is a received notification
            if (
                !array_key_exists('Tipo', $data) &&
                array_key_exists('IdentificativoSdI', $data) &&
                array_key_exists('NomeFile', $data)
            ) {
                $this->received($data);
            } else {
                // Check for request integrity
                if (
                    !array_key_exists('SdiId', $data) ||
                    !array_key_exists('IdentificativoSdI', $data) ||
                    !array_key_exists('NomeFile', $data) ||
                    !array_key_exists('File', $data)
                ) {
                    // For example, if processing is successful
                    http_response_code(422); // HTTP 200 OK
                    header('Content-Type: application/json');
                    // Process response if needed
                    return json_encode([
                        'message' => 'missing payload data'
                    ]);
                }

                $this->updated(new FileSdI((object)$data));
            }
            // For example, if processing is successful
            http_response_code(200); // HTTP 200 OK
            header('Content-Type: application/json');
            // Process response if needed
            return json_encode([
                'message' => 'file received correctly'
            ]);
        } catch (Exception $e) {
            $this->failed($e);

            // For example, if processing is successful
            http_response_code(400); // HTTP 200 OK
            header('Content-Type: application/json');
            // Process response if needed
            return json_encode([
                'message' => 'Unable to generate sdi file'
            ]);
        }

    }

    /**
     * Triggered when new invoice did receive from gateway
     * @param array $params
     * @return void
     */
    public abstract function received(array $params): void;

    /**
     * Triggered when an invoice receive an update status
     * @param FileSdI $fileSdI
     * @return void
     */
    public abstract function updated(FileSdI $fileSdI): void;

    /**
     * Triggered when there is an error sending an invoice
     * @param Exception $exception
     * @return void
     */
    public abstract function failed(Exception $exception): void;

    /**
     * Signature to sign all the requests
     * @return string
     */
    public abstract function signatureSecret(): string;

}