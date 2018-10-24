<?php
/**
 * @author Joakim Winum Lien <joakim@winum.xyz>
 * @license https://opensource.org/licenses/mit-license.html MIT License
 * @since File available since Release: 1.0.0
 */

use joakimwinum\weatherforecast\WeatherForecast as WeatherForecast;

require __DIR__ . '/vendor/autoload.php';

$weatherForecast = new WeatherForecast();
$weatherForecast->start();
