<?php

namespace Kunnu\Dropbox\Http\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Exception\RequestException;
use Kunnu\Dropbox\Http\DropboxRawResponse;
use Kunnu\Dropbox\Exceptions\DropboxClientException;

/**
 * DropboxGuzzleHttpClient.
 */
class DropboxGuzzleHttpClient implements DropboxHttpClientInterface
{
    /**
     * GuzzleHttp client
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * Create a new DropboxGuzzleHttpClient instance
     *
     * @param Client $client GuzzleHttp Client
     */
    public function __construct(Client $client = null) {
        if (!$client) {
            $client = new Client();
        }
        $this->client = $client;
    }

    /**
     * Send request to the server and fetch the raw response.
     *
     * @param  string $url     URL/Endpoint to send the request to
     * @param  string $method  Request Method
     * @param  string|resource|StreamInterface $body Request Body
     * @param  array  $headers Request Headers
     * @param  array  $options Additional Options
     *
     * @return \Kunnu\Dropbox\Http\DropboxRawResponse Raw response from the server
     *
     * @throws \Kunnu\Dropbox\Exceptions\DropboxClientException
     */
    public function send($url, $method, $body, $headers = [], $options = []) {
        //Create a new Request Object
        if (isset($options['sink'])) {
            $options['save_to'] = $options['sink'];
            unset($options['sink']);
        }
        $request = $this->client->createRequest($method, $url, $options);
        $request->setHeaders($headers);
        if ($body) {
            $request->setBody(Stream::factory($body));
        }

        try {
            //Send the Request
            $rawResponse = $this->client->send($request);
        } catch (RequestException $e) {
            $rawResponse = $e->getResponse();

            throw new DropboxClientException($e->getMessage(), $e->getCode());
        }

        //Something went wrong
        if ($rawResponse->getStatusCode() >= 400) {
            throw new DropboxClientException($rawResponse->getBody());
        }

        if (array_key_exists('save_to', $options)) {
            //Response Body is saved to a file
            $body = '';
        } else {
            //Get the Response Body
            $body = $this->getResponseBody($rawResponse);
        }

        $rawHeaders = $rawResponse->getHeaders();
        $httpStatusCode = $rawResponse->getStatusCode();

        //Create and return a DropboxRawResponse object
        return new DropboxRawResponse($rawHeaders, $body, $httpStatusCode);
    }

    /**
     * Get the Response Body
     *
     * @param string|GuzzleHttp\Message\Response $response Response object
     *
     * @return string
     */
    protected function getResponseBody($response) {
        //Response must be string
        $body = $response->getBody();
        if ($body instanceof GuzzleHttp\Stream\Stream) {
            $body = $body->getContents();
        }

        return $body;
    }
}
