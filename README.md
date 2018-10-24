# PHP Weather Forecast

This is a PHP CLI implementation of Yr's XML weather forecast data.

This work is done independent from Yr and NRK.

It uses predefined lists and does a quick search, alternatively it will try to do a fuzzy search.

## Getting Started

Clone the repository

Install with composer:

```composer install```

Run the CLI:

```php weatherforecast.php```

<b>Arguments available:</b>

Search for city/place/country

<b>Options available:</b>

```--scope=norway```
```--scope=zip```
```--scope=world```

Choose between Norway<i>(defualt)</i>, zip<i>(norwegian zip codes)</i> and the world

```--hourly```
This allows you to see the hourly weather forecast

```--text```
This gives you the Norwegian text forecast

```--help```
Displays a help message

```--language=english```
```--language=bokmaal```
```--language=nynorsk```

Choose which language you would prefer to view the forecast in. The default language is english

## Examples

```php weatherforecast.php oslo --hourly```

```php weatherforecast.php --text```

```php weatherforecast.php 0150 --scope=zip```

```php weatherforecast.php amsterdam --scope=world```

## Weather Forecast Sources

* The Yr forecast in XML-format
[Link](https://www.yr.no/artikkel/vervarsel-i-xml-format-1.3316805) (2018-10-21).
* The Yr XML-format explanation
[Link](https://www.yr.no/artikkel/forklaring-til-xml-formatet-1.5148662) (2018-10-21).
* 6170 places in Norway txt list
[Link](https://fil.nrk.no/yr/viktigestader/noreg.txt) (2018-10-21).
* 4585 zip codes in Norway txt list
[Link](https://fil.nrk.no/yr/viktigestader/postnummer.txt) (2018-10-21).
* 2506 places in rest of the world txt list
[Link](https://fil.nrk.no/yr/viktigestader/verda.txt) (2018-10-21).

## Authors

* **Joakim Winum Lien** - *Initial work* - [joakimwinum](https://github.com/joakimwinum)

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details
