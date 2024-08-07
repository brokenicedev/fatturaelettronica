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
    public string $endPoint = 'http://127.0.0.1:8002';

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
     */
    public function __construct(string $email, string $password, string $signature)
    {
        $this->email = $email;
        $this->password = $password;
        $this->signature = $signature;
    }

    /**
     * Invia un documento (fattura, nota di credito, nota di debito, etc.) al SdI, tramite Fattura Elettronica API
     * L'XML deve corrispondere al formato ministeriale: https://www.agenziaentrate.gov.it/portale/web/guest/specifiche-tecniche-versione-1.8
     * Il sistema aggiungerà o modificherà la sezione relativa ai dati di trasmissione (sezione FatturaElettronicaHeader/DatiTrasmissione dell'XML)
     * @param string $xml
     * @return void
     * @throws Exception
     */
    public function sendInvoice(string $xml): void
    {
        try {
            $token = $this->getAccessToken();

            if ($token === null) {
                throw new Exception('Unable to obtain access token.');
            }

            // Initialize Guzzle client
            $client = new Client();

            // Make the API call to send the invoice
            $response = $client->get($this->endPoint . '/api/sdicoop/customer', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ],
                'xml' => $xml
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

        $response = $oauth2Client->post($this->endPoint."/sdicoop/login", [
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
}