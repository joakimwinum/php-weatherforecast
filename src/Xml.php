<?php
/**
 * @author Joakim Winum Lien <joakim@winum.xyz>
 * @license https://opensource.org/licenses/mit-license.html MIT License
 * @since Class available since Release: 1.0.0
 */

namespace joakimwinum\weatherforecast;

use Laravie\Parser\Xml\Reader as XmlReader;
use Laravie\Parser\Xml\Document as XmlDocument;

class Xml
{
    public function getForecastArray($xmlContents)
    {
        $document = new XmlDocument();

        $document->setContent($xmlContents);

        $xmlString = $document->getContent();

        $xml = (new XmlReader($document))->extract($xmlString);

        $all = $xml->getOriginalContent();
        $rawAll = json_decode(json_encode($all), true);

        return $rawAll;
    }

    public function getHourlyXmlUrl($xmlContents)
    {
        $rawAll = $this->getForecastArray($xmlContents);

        $hourlyForecastUrl = $rawAll["links"]["link"][1]["@attributes"]["url"];

        return $hourlyForecastUrl;
    }

    public function convertDate($date, $format = null)
    {
        // date
        $newFormat = "Y-m-d";
        if ($format == "time") {
            $newFormat = "H:i";
        } else if ($format == "day") {
            $newFormat = "l";
        }

        $dateTime = new \DateTime($date);

        return $dateTime->format($newFormat);
    }
}