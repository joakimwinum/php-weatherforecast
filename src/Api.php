<?php
/**
 * @author Joakim Winum Lien <joakim@winum.xyz>
 * @license https://opensource.org/licenses/mit-license.html MIT License
 * @since Class available since Release: 1.0.0
 */

namespace joakimwinum\weatherforecast;

use GuzzleHttp\Client as Client;
use GuzzleHttp\Exception\GuzzleException as GuzzleException;

class Api
{
    public function getForecastXml($xmlUrl)
    {
        $client = new Client();

        try {
            $resource = $client->request('GET', $xmlUrl);
        } catch (GuzzleException $e) {
            echo "Exception message: ".$e->getMessage();
            return false;
        }

        $contents = $resource->getBody()->getContents();

        return $contents;
    }
}