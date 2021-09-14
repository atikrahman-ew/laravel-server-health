# Laravel Server Health


[![Software License][ico-license]](LICENSE.md)



**Note:**

This package adds url that will show basic server information and can enhance to monitor real time.

## Structure

If any of the following are applicable to your project, then the directory structure should follow industry best practices by being named the following.

```
src/
tests/
```


## Install

Via Composer

``` bash
$ composer require atikrahman/laravel-server-health
```

## Usage

```
Visit base_url/laravel-server to check health information.
```
``` php
/*Basic Functions to call infromation*/
 $system_bios = new SystemBios();
        //$system_bios->getOsInfo()
        //$system_bios->getOsInfo()

dd($system_bios->getTotalRam(),$system_bios->getFreeRam(),$system_bios->getCpuLoadPercentage(),$system_bios->getDiskSize(),$system_bios->getOsTasks(),$system_bios->getOsInfo());


```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ php tests/ExampleTest.php
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email atikrahman.ew@gmail.com instead of using the issue tracker.

## Credits

- [Atikur Rahman][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.


[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[link-author]: https://github.com/atikrahman-ew
