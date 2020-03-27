<?php declare(strict_types=1);

namespace Shopware\Production\LocalDelivery\DeliveryRoute\Services;

use GuzzleHttp\Client;
use Shopware\Core\Framework\Context;
use Shopware\Production\LocalDelivery\MapApiRequestLimiter\Services\MapApiRequestLimiterService;

class MapboxService
{
    /**
     * Mapbox specific endpoint that describes the api.
     * There is another option that is paid only.
     */
    private const ENDPOINT = 'mapbox.places';

    /**
     * @var MapApiRequestLimiterService
     */
    private $mapApiRequestLimiterService;

    /**
     * @var string
     */
    private $mapboxApiKey;

    /**
     * @var Client
     */
    private $client;

    public function __construct(MapApiRequestLimiterService $mapApiRequestLimiterService)
    {
        $this->mapApiRequestLimiterService = $mapApiRequestLimiterService;
        $this->mapboxApiKey = getenv('MAPBOX_API_KEY');
        $this->client = new Client([
            'base_uri' => 'https://api.mapbox.com',
            'timeout' => 2.0
        ]);
    }

    /**
     * @param string $searchtext
     * @return array [long, lat]
     * @throws \Exception
     */
    public function getGpsCoordinates(string $searchtext, Context $context) : array
    {
        if (!$this->mapApiRequestLimiterService->increaseCount('search-temporary-geocoding-api', $context)) {
            throw new \Exception('Map api limit reached for search-temporary-geocoding-api');
        }

        $response = $this->client->get('/geocoding/v5/' . self::ENDPOINT . '/' . $searchtext . '.json', [
            'query' => [
                'access_token' => $this->mapboxApiKey,
                'limit' => 1,
                'types' => 'address'
            ]
        ]);
        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Error from mapbox");
        }

        $data = json_decode($response->getBody()->getContents(), true);

        if (empty($data['features'])) {
            throw new \Exception("No coordinates found.");
        }

        return $data['features'][0]['center'];
    }

    /**
     * @param array $coordinatesArray
     * @param string $profile
     * @return array
     * @throws \Exception
     */
    public function getOptimizedRoute(array $coordinatesArray, string $profile, Context $context) {
        if (!$this->mapApiRequestLimiterService->increaseCount('navigation-optimization-api', $context)) {
            throw new \Exception('Map api limit reached for navigation-optimization-api');
        }

        $coordinatesQueryString = '';

        foreach ($coordinatesArray as $index => $coordinates) {
            if($index != 0) {
                $coordinatesQueryString = $coordinatesQueryString . ';';
            }
            $coordinatesQueryString = $coordinatesQueryString . $coordinates[0] . ',' . $coordinates[1];
        }

        $response = $this->client->get('/optimized-trips/v1/mapbox/' . $profile . '/' . $coordinatesQueryString, [
            'query' => [
                'access_token' => $this->mapboxApiKey
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Error from mapbox");
        }

        $result = json_decode($response->getBody()->getContents(), true);
        return $result['waypoints'];
    }
}
