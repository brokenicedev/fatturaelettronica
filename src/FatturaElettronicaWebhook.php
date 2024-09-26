<?php

namespace Brokenice\FatturaElettronica;

use Exception;

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
            $isValid = $validator->isValid($headerData['signature'], $this->signatureSecret(), $input);
            if (!$isValid) {
                // For example, if processing is successful
                http_response_code(401); // HTTP 400 OK
                header('Content-Type: application/json');
                // Process response if needed
                return json_encode([
                    'message' => 'Invalid signature'
                ]);
            }

            // Decode JSON payload if applicable
            $data = json_decode($input, true);

            // Check for errors
            if (array_key_exists('Error', $data) && !empty(trim($data['Errore']))) {
                return $this->failed($data);
            } else if (
                !array_key_exists('Tipo', $data) &&
                array_key_exists('IdentificativoSdI', $data) &&
                array_key_exists('NomeFile', $data)
            ) {
                return $this->received($data);
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

                return $this->updated(new FileSdI((object)$data));
            }

        } catch (Exception $e) {
            \Log::error($e->getMessage());

            // For example, if processing is successful
            http_response_code(400); // HTTP 400 OK
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
     * @return mixed
     */
    public abstract function received(array $params): mixed;

    /**
     * Triggered when an invoice receive an update status
     * @param FileSdI $fileSdI
     * @return mixed
     */
    public abstract function updated(FileSdI $fileSdI): mixed;

    /**
     * Triggered when there is an error sending an invoice
     * @param mixed $exception
     * @return mixed
     */
    public abstract function failed(mixed $exception): mixed;

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
