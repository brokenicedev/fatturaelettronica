<?php

namespace Brokenice\FatturaElettronica;

use Exception;
use JetBrains\PhpStorm\NoReturn;

abstract class FatturaElettronicaWebhook
{

    /**
     * @var string[]
     */
    public static array $types = [
        'File metadati' => '_MT_',
        'Notifica di scarto' => '_NS_',
        'Notifica di consegna' => '_NC_',
        'Ricevuta di consegna' => '_RC_',
        'Mancata consegna' => '_MC_',
        'Notifica esito committente' => '_EC_',
        'Notifica scarto esito committente' => '_SE_',
        'Notifica esito versione' => '_NE_',
        'Notifica decorrenza termini versione' => '_DT_',
        'Avvenuta trasmissione impossibilità recapito' => '_AT_',
    ];

    /**
     * @return string|bool
     * @throws Exception
     */
    public function handle(): ?bool
    {
        $headerData = array_change_key_case(getallheaders(), CASE_LOWER);
        // Retrieve raw POST data from php://input
        $input = file_get_contents('php://input');

        $validator = new DefaultSignatureValidator();

        try {
            $isValid = $validator->isValid($headerData['signature'] ?? null, $this->signatureSecret(), $input);
            if (!$isValid) {
                $this->response('Invalid signaure', 401);
            }

            // Decode JSON payload if applicable
            $data = json_decode($input, true);

            // Check for errors
            if (array_key_exists('Error', $data) && !empty(trim($data['Errore']))) {
                $this->failed($data);
            } else if (
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
                    $this->response('Missing payload data', 422);
                }

                $this->updated(new FileSdI((object)$data));
            }

        } catch (Exception $e) {
            \Log::error($e->getMessage());
            $this->response('Unable to generate sdi file', 400);
        }

    }

    /**
     * Called to completed endpoint handle
     * @param string $message
     * @return void
     */
    #[NoReturn] public function ack(string $message = 'ACK'): void
    {
        $this->response($message, 200);
    }

    /**
     * Called to block endpoint handle
     * @param string $message
     * @return void
     */
    #[NoReturn] public function nack(string $message = 'NACK'): void
    {
        $this->response($message, 400);
    }

    /**
     * @param mixed $message
     * @param int $status
     * @return void
     */
    #[NoReturn] protected function response(mixed $message, int $status = 200): void
    {
        // Set HTTP status code
        http_response_code($status); // HTTP 401 Unauthorized
        // Set content-type to JSON
        header('Content-Type: application/json');

        // Output JSON response
        echo json_encode([
            'message' => $message
        ]);

        // Ensure no further output
        exit();
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
     * @param mixed $exception
     * @return void
     */
    public abstract function failed(mixed $exception): void;

    /**
     * Signature to sign all the requests
     * @return string
     */
    public abstract function signatureSecret(): string;

    /**
     * @param $fileName
     * @return string
     */
    public function typeFromName($fileName): string
    {
        foreach (static::$types as $key => $value) {
            if (str_contains($fileName, $value)) {
                return str_replace("_", "", $value);
            }
        }
        return 'FE';
    }

}
