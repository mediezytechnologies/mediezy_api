<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class DistanceMatrixService
{
    protected $apiKey;
    protected $baseUrl = 'https://maps.googleapis.com/maps/api/distancematrix/json';

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getDistance($originLat, $originLng, $destinationLat, $destinationLng)
    {

        $client = new Client();

        $url = $this->buildUrl($originLat, $originLng, $destinationLat, $destinationLng);

        $response = $client->get($url);

        if ($response->getStatusCode() !== 200) {
            Log::error("Error: Failed to fetch distance data. Status Code: " . $response->getStatusCode());
            return null;
        }

        $data = json_decode($response->getBody(), true);

        if (!isset($data['status'])) {
            Log::error("Error: Unexpected response format. Response: " . $response->getBody());
            return null;
        }

        if ($data['status'] === 'OK') {
            if (isset($data['rows'][0]['elements'][0]['distance']['text'])) {
                $distance = $data['rows'][0]['elements'][0]['distance']['text'];

                if (strpos($distance, ' m') !== false) {
                    $distanceValue = (float) $distance;
                    $distanceInKm = $distanceValue / 1000;
                    $distance = $distanceInKm . ' km';
                }


                return $distance;
            } else {
                return null;
            }
        } else {
            Log::error("Error: Failed to retrieve distance. Status: " . $data['status']);
            return null;
        }
    }

    // protected function buildUrl($originLat, $originLng, $destinationLat, $destinationLng)
    // {
    //     $origin = $originLat . ',' . $originLng;
    //     $destination = $destinationLat . ',' . $destinationLng;
    //     $url = $this->baseUrl . '?origins=' . urlencode($origin) . '&destinations=' . urlencode($destination) . '&key=' . $this->apiKey;
    //     return $url;
    // }


    public function getDistances($originLat, $originLng, $destinationLat, $destinationLng)
    {
        // $client = new Client();
        // $url = $this->buildUrl($originLat, $originLng, $destinationLat, $destinationLng);

        // Log::info("Requesting Distance Matrix: $url");

        // try {
        // $response = $client->get($url);
        // $statusCode = $response->getStatusCode();
        // $body = $response->getBody()->getContents();

        // Log::info("API Response: $body");

        // if ($statusCode !== 200) {
        //     Log::error("Error: Failed to fetch distance data. Status Code: $statusCode");
        //     return null;
        // }

        // $data = json_decode($body, true);

        // if (!isset($data['status'])) {
        //     Log::error("Error: Unexpected response format.");
        //     return null;
        // }

        // if ($data['status'] !== 'OK') {
        //     Log::error("Error: Failed to retrieve distance. Status: " . $data['status']);
        //     return null;
        // }

        // if (isset($data['rows'][0]['elements'][0]['distance']['text'])) {
        //     $distance = $data['rows'][0]['elements'][0]['distance']['text'];
        //     if (strpos($distance, ' m') !== false) {
        //         $distanceValue = (float) $distance;
        //         $distanceInKm = $distanceValue / 1000;
        //         $distance = $distanceInKm . ' km';
        //     }
        //     return $distance;
        // }

        return null;
        // } catch (\Exception $e) {
        //     Log::error("Exception during Distance Matrix API call: " . $e->getMessage());
        //     return null;
        // }
    }

    protected function buildUrl($originLat, $originLng, $destinationLat, $destinationLng)
    {
        $origin = $originLat . ',' . $originLng;
        $destination = $destinationLat . ',' . $destinationLng;
        $url = $this->baseUrl . '?origins=' . urlencode($origin) . '&destinations=' . urlencode($destination) . '&key=' . $this->apiKey;
        // https://maps.googleapis.com/maps/api/distancematrix/json?origins=1600+Amphitheatre+Parkway,+Mountain+View,+CA&destinations=111+8th+Avenue,+New+York,+NY&key=YOUR_API_KEY

        return $url;
    }
}
