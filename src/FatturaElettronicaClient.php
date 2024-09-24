<?php
namespace Brokenice\FatturaElettronica;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Libreria Client PHP per utilizzare il servizio Fattura Elettronica - https://fatturaelettronica.brokenice.it
 * Guida e registrazione: https://gestionefatture.brokenice.it
 * @author Becchetti Luca
 * @version 1.0
 * @since 2024-08-07
 */
class FatturaElettronicaClient
{

    /**
     * Endpoint per il sistema di fatturazione
     * @var string
     */
    public string $endPoint = 'https://gestionefatture.brokenice.it';

    /**
     * Email to authenticate
     * @var string
     */
    public string $email;

    /**
     * Password to authenticate
     * @var string
     */
    public string $password;

    /**
     * Password to authenticate
     * @var string
     */
    public string $signature;

    /**
     * @param string $email
     * @param string $password
     * @param string $signature
     * @param string|null $endPoint
     */
    public function __construct(string $email, string $password, string $signature, string $endPoint = null)
    {
        $this->email = $email;
        $this->password = $password;
        $this->signature = $signature;
        if (!is_null($endPoint)) {
            $this->endPoint = $endPoint;
        }
    }

    /**
     * Invia un documento (fattura, nota di credito, nota di debito, etc.) al SdI, tramite Fattura Elettronica API
     * L'XML deve corrispondere al formato ministeriale: https://www.agenziaentrate.gov.it/portale/web/guest/specifiche-tecniche-versione-1.8
     * Il sistema aggiungerà o modificherà la sezione relativa ai dati di trasmissione (sezione FatturaElettronicaHeader/DatiTrasmissione dell'XML)
     * @param string $xml
     * @param string $documentNumber
     * @return void
     * @throws Exception
     */
    public function sendInvoice(string $xml, ...$parameters): void
    {
        try {
            $token = $this->getAccessToken();

            if ($token === null) {
                throw new Exception('Unable to obtain access token.');
            }

            // Initialize Guzzle client
            $client = new Client();

            // Make the API call to send the invoice
            $response = $client->post($this->getEndPoint() . '/api/sdicoop/send-invoice', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ],
                'form_params' => [
                    'xml' => $xml,
                    ...$parameters
                ]
            ]);

            if($response->getStatusCode() !== 200) {
                throw new Exception('Request failed: ' . $response->getBody());
            }

        } catch (GuzzleException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * This method is used to get access token from the endpoint
     * @return mixed
     * @throws GuzzleException
     */
    protected function getAccessToken(): mixed
    {
        $oauth2Client = new Client();

        $response = $oauth2Client->post($this->getEndPoint()."/sdicoop/login", [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'form_params' => [
                'email' => $this->email,
                'password' => $this->password
            ],
        ]);

        $responseData = json_decode($response->getBody(), true);
        return $responseData['token'];
    }

    /**
     * Get current endPoint
     * @return string
     */
    protected function getEndPoint(): string
    {
        return $this->endPoint;
    }
}