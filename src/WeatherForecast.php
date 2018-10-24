<?php
/**
 * @author Joakim Winum Lien <joakim@winum.xyz>
 * @license https://opensource.org/licenses/mit-license.html MIT License
 * @since Class available since Release: 1.0.0
 */

namespace joakimwinum\weatherforecast;

use Symfony\Component\Console\Application as Application;
use joakimwinum\weatherforecast\command\WeatherForecastCommand as WeatherForecastCommand;

class WeatherForecast
{
    public function start()
    {
        $application = new Application();
        $command = new WeatherForecastCommand();
        $application->add($command);
        $application->setDefaultCommand($command->getName(), true);

        try {
            $application->run();
        } catch (\Exception $e) {
            echo "Can't run the command. Exception message: ".$e->getMessage();
        }
    }
}