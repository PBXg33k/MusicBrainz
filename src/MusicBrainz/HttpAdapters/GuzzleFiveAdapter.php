<?php
namespace MusicBrainz\HttpAdapters;

use GuzzleHttp\ClientInterface;
use MusicBrainz\Exception;

class GuzzleFiveAdapter extends AbstractHttpAdapter
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * Initialize class
     *
     * @param ClientInterface $client
     * @param null $endpoint
     */
    public function __construct(ClientInterface $client, $endpoint = NULL)
    {
        $this->client = $client;

        if (filter_var($endpoint, FILTER_VALIDATE_URL)) {
            $this->endpoint = $endpoint;
        }
    }

    /**
     * Perform an HTTP request on MusicBrainz
     *
     * @param  string $path
     * @param  array $params
     * @param  array $options
     * @param  boolean $isAuthRequired
     * @param  boolean $returnArray
     *
     * @throws Exception
     * @return array
     */
    public function call($path, array $params = array(), array $options = array(), $isAuthRequired = FALSE, $returnArray = FALSE)
    {
        if ($options['user-agent'] == '') {
            throw new Exception('You must set a valid User Agent before accessing the MusicBrainz API');
        }

        $requestOptions = [
            'headers'        => [
                'Accept'     => 'application/json',
                'User-Agent' => $options['user-agent']
            ],
            'query' => $params
        ];

        if ($isAuthRequired) {
            if ($options['user'] != NULL && $options['password'] != NULL) {
                $requestOptions['auth'] = [
                    'username' => $options['user'],
                    'password' => $options['password'],
                    CURLAUTH_DIGEST
                ];
            } else {
                throw new Exception('Authentication is required');
            }
        }

        $response = $this->client->request('GET', $this->endpoint . '/' . $path, $requestOptions);

        // musicbrainz throttle
        sleep(1);

        $responseBody = $response->getBody()->getContents();
        $jsonResponse = \GuzzleHttp\json_decode($responseBody);

        if(json_last_error() === JSON_ERROR_NONE) {
            if($returnArray) {
                return get_object_vars($jsonResponse);
            }
            return $jsonResponse;
        }

        throw new Exception('Expected JSON response. Got:' . $responseBody );
    }
}