<?php
/**
 * @author Joakim Winum Lien <joakim@winum.xyz>
 * @license https://opensource.org/licenses/mit-license.html MIT License
 * @since Class available since Release: 1.0.0
 */

namespace joakimwinum\weatherforecast;

use League\Csv\Reader as CsvReader;
use League\Csv\Statement as CsvStatement;
use League\Csv\Exception as Exception;
use Fuse\Fuse as Fuse;

class Search
{
    const NORWAY_CSV_FILE = "data/noreg.csv";
    const NORWAY_ZIP_CSV_FILE = "data/postnummer.csv";
    const WORLD_CSV_FILE = "data/verda.csv";

    const LANGUAGE_ENGLISH = 0;
    const LANGUAGE_BOKMAAL = 1;
    const LANGUAGE_NYNORSK = 2;

    protected $searchArray;

    /**
     * @return mixed
     */
    public function getSearchArray()
    {
        return $this->searchArray;
    }

    /**
     * @param mixed $searchArray
     */
    public function setSearchArray($searchArray): void
    {
        $this->searchArray = $searchArray;
    }

    public function search($searchPattern, $progressBar, $searchFile = self::WORLD_CSV_FILE, $language = self::LANGUAGE_ENGLISH, $allowFuseSearch = true)
    {
        $keys = $this->getKeys($searchFile, $language);
        $this->loadCsvTableForSearch($searchFile, $keys);
        $progressBar->advance(5);

        $arrayToSearch = $this->getSearchArray();
        $progressBar->advance(5);

        $keys = array_keys($keys);
        array_pop($keys);

        $quickSearch = $this->quickSearch($arrayToSearch, $keys, $searchPattern);
        $progressBar->advance(10);

        if ($quickSearch !== false) {
            $result = $arrayToSearch[$quickSearch];
        } else if ($allowFuseSearch) {
            $progressBar->advance(20);
            $fuseSearch = $this->fuseSearch($arrayToSearch, $keys, $searchPattern);
        }

        if (isset($fuseSearch) && $fuseSearch !== false) {
            $result = array_shift($fuseSearch);
        }

        if (!isset($result)) {
            return false;
        }

        return $result;
    }

    protected function fuseSearch($arrayToSearch, $keys, $searchPattern)
    {
        $fuse = new Fuse(
            $arrayToSearch,
            [
                "keys" => $keys,
                "includeScore" => false,
                "shouldSort" => true
            ]
        );

        $result = $fuse->search($searchPattern);

        $tmp = [];

        if (count($result) > 3) {
            for ($i=0; $i < 3; $i++) {
                $tmp[] = array_shift($result);
            }

            $result = $tmp;
            unset($tmp);
        }

        if (empty($result)) {
            return false;
        }

        return $result;
    }

    protected function quickSearch($arrayToSearch, $keys, $searchPattern)
    {
        foreach ($arrayToSearch as $id => $val) {
            foreach ($keys as $key) {
                if (strtolower($val[$key]) === strtolower($searchPattern)) {
                    return $id;
                }
            }
        }
        return false;
    }

    public function loadCsvTableForSearch($file, $keys)
    {
        $reader = CsvReader::createFromPath($file, 'r');

        try {
            $reader->setDelimiter(',');
        } catch (Exception $e) {
            echo "Exception message: ".$e->getMessage();
            return false;
        }

        try {
            $reader->setHeaderOffset(0);
        } catch (Exception $e) {
            echo "Exception message: ".$e->getMessage();
            return false;
        }

        $records = $reader->getRecords();

        $searchArray = [];

        foreach ($records as $offset => $record) {
            foreach ($keys as $key => $field) {
                $searchArray[$offset][$key] = $record[$field];
            }
        }
        sort($searchArray);

        $this->setSearchArray($searchArray);

        return $searchArray;
    }

    public function getKeys($file, $language)
    {
        $keysArray = [
            "norway" => [
                [
                    "state" => "Stadnamn",
                    "xml-url" => "Engelsk"
                ],
                [
                    "state" => "Stadnamn",
                    "xml-url" => "Bokmål"
                ],
                [
                    "state" => "Stadnamn",
                    "xml-url" => "Nynorsk"
                ],
            ],
            "zip" => [
                [
                    "zip" => "Postnr",
                    "xml-url" => "Engelsk"
                ],
                [
                    "zip" => "Postnr",
                    "xml-url" => "Bokmål"
                ],
                [
                    "zip" => "Postnr",
                    "xml-url" => "Nynorsk"
                ],
            ],
            "world" => [
                [
                    "state" => "Stadnamn engelsk",
                    "country" => "Landsnamn engelsk",
                    "xml-url" => "Lenke til engelsk-XML"
                ],
                [
                    "state" => "Stadnamn bokmål",
                    "country" => "Landsnamn bokmål",
                    "xml-url" => "Lenke til bokmåls-XML"
                ],
                [
                    "state" => "Stadnamn nynorsk",
                    "country" => "Landsnamn nynorsk",
                    "xml-url" => "Lenke til nynorsk-XML"
                ],
            ],
        ];

        if ($file == self::NORWAY_CSV_FILE) {
            $keys = $keysArray["norway"];
        } else if ($file == self::NORWAY_ZIP_CSV_FILE) {
            $keys = $keysArray["zip"];
        } else {
            $keys = $keysArray["world"];
        }

        return $keys[$language];
    }
}