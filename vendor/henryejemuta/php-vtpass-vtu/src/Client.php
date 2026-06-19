<?php

namespace HenryEjemuta\Vtpass;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;

class Client
{
    /**
     * The base URL for the VTpass API.
     */
    private const BASE_URL = 'https://vtpass.com/api/';
    private const SANDBOX_BASE_URL = 'https://sandbox.vtpass.com/api/';

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $publicKey;

    /**
     * @var string
     */
    private $secretKey;

    /**
     * @var GuzzleClient
     */
    private $httpClient;

    /**
     * Client constructor.
     *
     * @param string $apiKey
     * @param string $publicKey
     * @param string $secretKey
     * @param array $config
     */
    public function __construct(string $apiKey, string $publicKey, string $secretKey, array $config = [])
    {
        $this->apiKey = $apiKey;
        $this->publicKey = $publicKey;
        $this->secretKey = $secretKey;

        $baseUrl = $config['base_url'] ?? self::BASE_URL;
        if (isset($config['sandbox']) && $config['sandbox'] === true) {
            $baseUrl = self::SANDBOX_BASE_URL;
        }

        // Ensure base URL ends with a slash
        if (substr($baseUrl, -1) !== '/') {
            $baseUrl .= '/';
        }

        $timeout = $config['timeout'] ?? 30;

        // Standard Guzzle configuration
        $guzzleConfig = [
            'base_uri' => $baseUrl,
            'timeout' => $timeout,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ];

        // Merge user config into Guzzle config (preserving keys, user config overwrites defaults if needed, 
        // but typically we just want to add things like 'handler')
        $guzzleConfig = array_merge($guzzleConfig, $config);

        /*
         * Note: VTpass documentation specifies:
         * GET request: api-key and public-key
         * POST request: api-key and secret-key
         * We will add these dynamically in the request method.
         */

        $this->httpClient = new GuzzleClient($guzzleConfig);
    }

    /**
     * Generate a unique Request ID.
     * Format: YYYYMMDDHHII + random string.
     *
     * @return string
     */
    public function generateRequestId(): string
    {
        // Set timezone to Lagos as per docs
        $date = new \DateTime("now", new \DateTimeZone('Africa/Lagos'));
        $prefix = $date->format('YmdHi'); // YYYYMMDDHHII
        $random = bin2hex(random_bytes(5)); // Random string to ensure uniqueness
        return $prefix . $random;
    }

    /**
     * Make a request to the API.
     *
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return array
     * @throws VtpassException
     */
    private function request(string $method, string $uri, array $options = []): array
    {
        $headers = [
            'api-key' => $this->apiKey,
        ];

        if (strtoupper($method) === 'GET') {
            $headers['public-key'] = $this->publicKey;
        } else {
            $headers['secret-key'] = $this->secretKey;
        }

        // Merge headers with any existing options
        $options['headers'] = array_merge($options['headers'] ?? [], $headers);

        try {
            $response = $this->httpClient->request($method, $uri, $options);
            $content = $response->getBody()->getContents();
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new VtpassException('Failed to decode JSON response: ' . json_last_error_msg());
            }

            return $data;
        } catch (GuzzleException $e) {
            $message = $e->getMessage();
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                $errorData = json_decode($responseBody, true);
                if (isset($errorData['response_description'])) {
                    $message = $errorData['response_description'];
                } elseif (isset($errorData['message'])) {
                    $message = $errorData['message'];
                }
            }
            throw new VtpassException('API Request Failed: ' . $message, $e->getCode(), $e);
        }
    }

    /**
     * Get Service Categories.
     *
     * @return array
     * @throws VtpassException
     */
    public function getServiceCategories(): array
    {
        return $this->request('GET', 'service-categories');
    }

    /**
     * Get Service Variations.
     *
     * @param string $serviceID
     * @return array
     * @throws VtpassException
     */
    public function getServiceVariations(string $serviceID): array
    {
        return $this->request('GET', 'service-variations', [
            'query' => ['serviceID' => $serviceID]
        ]);
    }

    /**
     * Purchase a product/service.
     *
     * @param array $payload
     * @return array
     * @throws VtpassException
     */
    public function purchase(array $payload): array
    {
        // Ensure request_id is present
        if (!isset($payload['request_id'])) {
            $payload['request_id'] = $this->generateRequestId();
        }

        return $this->request('POST', 'pay', [
            'json' => $payload
        ]);
    }

    /**
     * Query Transaction Status.
     *
     * @param string $requestId
     * @return array
     * @throws VtpassException
     */
    public function queryTransactionStatus(string $requestId): array
    {
        return $this->request('POST', 'requery', [
            'json' => ['request_id' => $requestId]
        ]);
    }

    // --- Helper Methods ---

    /**
     * Purchase Airtime.
     *
     * @param string $serviceID (e.g., mtn, glo, airtel, etisalat)
     * @param float $amount
     * @param string $phone
     * @return array
     * @throws VtpassException
     */
    public function purchaseAirtime(string $serviceID, float $amount, string $phone): array
    {
        return $this->purchase([
            'serviceID' => $serviceID,
            'amount' => $amount,
            'phone' => $phone
        ]);
    }

    /**
     * Purchase Data.
     *
     * @param string $serviceID (e.g., mtn-data)
     * @param string $billersCode (Phone number)
     * @param string $variationCode (Plan code)
     * @param float|null $amount (Optional for some plans)
     * @param string|null $phone (The phone number to be debited, usually same as billersCode or user phone)
     * @return array
     * @throws VtpassException
     */
    public function purchaseData(string $serviceID, string $billersCode, string $variationCode, ?float $amount = null, ?string $phone = null): array
    {
        $payload = [
            'serviceID' => $serviceID,
            'billersCode' => $billersCode,
            'variation_code' => $variationCode,
            'phone' => $phone ?? $billersCode
        ];

        if ($amount !== null) {
            $payload['amount'] = $amount;
        }

        return $this->purchase($payload);
    }

    /**
     * Purchase Data.
     *
     * @return array
     * @throws VtpassException
     */
    public function getBalance(): array
    {
        return $this->request('GET', 'balance');
    }
}
