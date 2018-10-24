<?php
/**
 * @author Joakim Winum Lien <joakim@winum.xyz>
 * @license https://opensource.org/licenses/mit-license.html MIT License
 * @since Class available since Release: 1.0.0
 */

namespace joakimwinum\weatherforecast\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;
use joakimwinum\weatherforecast\Search as Search;
use joakimwinum\weatherforecast\Api as Api;
use joakimwinum\weatherforecast\Xml as Xml;

class WeatherForecastCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('weather:forecast')
            ->setDescription('Weather forecast.')
            ->setHelp('This command allows you to get weather forecast.')
            ->addArgument(
                'searchTerm',
                InputArgument::IS_ARRAY,
                'What do you want to search for (separate multiple names with a space)?',
                array('oslo')
            )
            ->addOption(
                'language',
                'lang',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Which language do you want to use?',
                array('english', 'bokmaal', 'nynorsk')
            )
            ->addOption(
                'scope',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'In what scope do you want to search?',
                array('norway', 'zip', 'world')
            )
            ->addOption(
                'tableformat',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Where do you want to search?',
                array('single', 'double', 'none')
            )
            ->addOption(
                'hourly',
                null,
                InputOption::VALUE_OPTIONAL,
                'Get hourly weather forecast.',
                false
            )
            ->addOption(
                'tabular',
                'tab',
                InputOption::VALUE_OPTIONAL,
                'Get tabular weather forecast.',
                false
            )
            ->addOption(
                'text',
                null,
                InputOption::VALUE_OPTIONAL,
                'Get text weather forecast.',
                false
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // construct
        $search = new Search;
        $api = new Api;
        $xml = new Xml;

        $this->search = $search;
        $this->api = $api;
        $this->xml = $xml;

        // setup
        $outputStyle = new OutputFormatterStyle('default', 'default', array('bold'));
        $output->getFormatter()->setStyle('bold', $outputStyle);
        $output->getFormatter()->setStyle('strong', $outputStyle);

        // argument and options
        $searchTerm = $input->getArgument('searchTerm');
        $searchScope = $input->getOption('scope')[0];
        $lang = $input->getOption('language')[0];
        $tableFormatInput = $input->getOption('tableformat')[0];

        // prepare table format
        $tableFormat = "box";
        if ($tableFormatInput == "double") {
            $tableFormat = "box-double";
        } else if ($tableFormatInput == "none") {
            $tableFormat = "borderless";
        }

        // find the scope of the search
        $whereToSearch = Search::NORWAY_CSV_FILE;
        if ($searchScope == "zip") {
            $whereToSearch = Search::NORWAY_ZIP_CSV_FILE;
        } else if ($searchScope == "world") {
            $whereToSearch = Search::WORLD_CSV_FILE;
        }

        // get the preferred language of the search
        $searchLanguage = Search::LANGUAGE_ENGLISH;
        if ($lang == "bokmaal") {
            $searchLanguage = Search::LANGUAGE_BOKMAAL;
        } else if ($lang == "nynorsk") {
            $searchLanguage = Search::LANGUAGE_NYNORSK;
        }

        // search with progressbar
        $searchTermReady = implode(" ", $searchTerm);
        $output->writeln("Searching for: ".$searchTermReady);
        $progressBar = new ProgressBar($output, 100);
        $progressBar->setFormat('%bar%');
        $progressBar->start();
        $progressBar->advance();
        $result = $this->search->search($searchTermReady, $progressBar, $whereToSearch, $searchLanguage);
        $progressBar->advance(20);
        $progressBar->finish();
        $output->writeln("");

        // xml url
        $xmlUrl = $result["xml-url"];

        // check for error
        if (!isset($xmlUrl)) {
            $this->notFound($input, $output);
            exit(1);
        }

        // clear screen
        $output->write(sprintf("\033\143"));

        // xml content
        $xmlContents = $this->api->getForecastXml($xmlUrl);

        // get hourly xml url
        $hourlyXmlUrl = $this->xml->getHourlyXmlUrl($xmlContents);

        // hourly xml content
        $hourlyXmlContents = $this->api->getForecastXml($hourlyXmlUrl);

        // get forecast arrays
        $xmlArray = $this->xml->getForecastArray($xmlContents);
        $hourlyXmlArray = $this->xml->getForecastArray($hourlyXmlContents);

        // forecast option
        if ($input->getOption('hourly') === null) {
            $this->hourlyForecast($input, $output, $hourlyXmlArray, $tableFormat);
        } else if ($input->getOption('text') === null) {
            $this->textForecast($input, $output, $xmlArray);
        } else {
            // default
            $this->tabularForecast($input, $output, $xmlArray, $tableFormat);
        }

        // credits
        $this->credits($input, $output, $xmlArray);
    }

    protected function credits($input, $output, $xmlArray)
    {
        $creditText = $xmlArray["credit"]["link"]["@attributes"]["text"];
        $creditUrl = $xmlArray["credit"]["link"]["@attributes"]["url"];

        $output->writeln("<bold>".$creditText."</bold>");
        $output->writeln("<bold>".$creditUrl."</bold>");
    }

    protected function notFound($input, $output)
    {
        $output->writeln("No search result found.");
    }

    protected function tabularForecast($input, $output, $xmlArray, $tableFormat)
    {
        $table = new Table($output);

        $header = array(
            "Day",
            "Time",
            "Temp.",
            "Precip.",
            "Wind"
        );

        $rows = [];

        $forecastTabular = $xmlArray["forecast"]["tabular"]["time"];
        $tableLength = count($forecastTabular);
        foreach ($forecastTabular as $key => $row) {
            $timeFrom = $row["@attributes"]["from"];
            $timeTo = $row["@attributes"]["to"];
            $windSpeedName = $row["windSpeed"]["@attributes"]["name"];
            $windSpeedMps = $row["windSpeed"]["@attributes"]["mps"];
            $windDirectionName = $row["windDirection"]["@attributes"]["name"];
            $precipitationValue = $row["precipitation"]["@attributes"]["value"];
            $precipitation = $precipitationValue;

            if (isset($row["precipitation"]["@attributes"]["minvalue"]) && isset($row["precipitation"]["@attributes"]["maxvalue"])) {
                $precipitationMin = $row["precipitation"]["@attributes"]["minvalue"];
                $precipitationMax = $row["precipitation"]["@attributes"]["maxvalue"];
                $precipitation = $precipitationMin." - ".$precipitationMax;
            }

            $temperatureUnit = strtoupper(substr($row["temperature"]["@attributes"]["unit"], 0, 1));
            $temperature = $row["temperature"]["@attributes"]["value"];

            if ($key%4 == 1 || $key == 0) {
                $day = $this->xml->convertDate($timeFrom, "day");
            } else if ($key%4 == 2) {
                $day = $this->xml->convertDate($timeFrom, "date");
            } else if ($key%4 == 3) {
                $day = "Y-m-d";
            } else {
                $day = "";
            }

            $rows[] = [
                $day,
                $this->xml->convertDate($timeFrom, "time")." - ".$this->xml->convertDate($timeTo, "time"),
                $temperature." ".$temperatureUnit,
                $precipitation,
                $windSpeedName.", ".$windSpeedMps." m/s from ".$windDirectionName,
            ];

            if ($key == 0 || $key%4 == 0 && $tableLength-1 != $key) {
                $rows[] = new TableSeparator();
            }
        }

        $table
            ->setHeaders($header)
            ->setRows($rows)
        ;

        $locationName = $xmlArray["location"]["name"];
        $locationCountry = $xmlArray["location"]["country"];
        $time = $this->xml->convertDate($xmlArray["forecast"]["tabular"]["time"][0]["@attributes"]["from"], "date");

        $output->writeln('<bold>Weather forecast</bold>');
        $output->writeln("<bold>".$locationName." ".$locationCountry." ".$time."</bold>");
        $table->setStyle($tableFormat);
        $table->render();
    }

    protected function hourlyForecast($input, $output, $xmlArray, $tableFormat)
    {
        $table = new Table($output);

        $header = array(
            "Time",
            "Temp.",
            "Precip.",
            "Wind"
        );

        $rows = [];

        $forecastHourly = $xmlArray["forecast"]["tabular"]["time"];
        foreach ($forecastHourly as $key => $row) {
            $timeFrom = $row["@attributes"]["from"];
            $timeTo = $row["@attributes"]["to"];
            $windSpeedName = $row["windSpeed"]["@attributes"]["name"];
            $windSpeedMps = $row["windSpeed"]["@attributes"]["mps"];
            $windDirectionName = $row["windDirection"]["@attributes"]["name"];
            $precipitationValue = $row["precipitation"]["@attributes"]["value"];
            $precipitation = $precipitationValue;

            if (isset($row["precipitation"]["@attributes"]["minvalue"]) && isset($row["precipitation"]["@attributes"]["maxvalue"])) {
                $precipitationMin = $row["precipitation"]["@attributes"]["minvalue"];
                $precipitationMax = $row["precipitation"]["@attributes"]["maxvalue"];
                $precipitation = $precipitationMin." - ".$precipitationMax;
            }

            $temperatureUnit = strtoupper(substr($row["temperature"]["@attributes"]["unit"], 0, 1));
            $temperature = $row["temperature"]["@attributes"]["value"];

            $day = $this->xml->convertDate($timeFrom, "day");
            $time = $this->xml->convertDate($timeFrom, "time");

            $rows[] = [
                $day." ".$time,
                $temperature." ".$temperatureUnit,
                $precipitation,
                $windSpeedName.", ".$windSpeedMps." m/s from ".$windDirectionName,
            ];
        }

        $table
            ->setHeaders($header)
            ->setRows($rows)
        ;

        $locationName = $xmlArray["location"]["name"];
        $locationCountry = $xmlArray["location"]["country"];
        $time = $this->xml->convertDate($xmlArray["forecast"]["tabular"]["time"][0]["@attributes"]["from"], "date");

        $output->writeln('<bold>Hourly weather forecast</bold>');
        $output->writeln("<bold>".$locationName." ".$locationCountry." ".$time."</bold>");
        $table->setStyle($tableFormat);
        $table->render();
    }

    protected function textForecast($input, $output, $xmlArray)
    {
        $rows = [];

        $forecastText = $xmlArray["forecast"]["text"]["location"]["time"];
        foreach ($forecastText as $key => $row) {
            $timeFrom = $row["@attributes"]["from"];
            $timeTo = $row["@attributes"]["to"];
            $title = $row["title"];
            $body = $row["body"];

            $day = $this->xml->convertDate($timeFrom, "day");

            $rows[] = [
                $day,
                $body,
            ];
        }

        $output->writeln('<bold>Text weather forecast</bold>');
        $output->writeln('');

        foreach($rows as $row) {
            $output->writeln('<bold>'.$row[0].'</bold>');
            $output->writeln($row[1]);
            $output->writeln('');
        }
    }
}