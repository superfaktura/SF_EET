# PHP Library for Czech EET system

## Installation
Install po1nt/eet using  [Composer](http://getcomposer.org/):

```sh
$ composer require po1nt/eet
```

### Dependencies
- PHP >=5.6
- robrichards/wse-php
- robrichards/xmlseclibs
- php extensions: php_openssl, php_soap, php_curl, php_mbstring

## Example Usage
Sample codes are located in examples/ folder

```php
use Po1nt\EET\Dispatcher;
use Po1nt\EET\Certufucate;
use Po1nt\EET\Receipt;
use Po1nt\EET\Utils\UUID;

$certificate = new Certificate(DIR_CERT . '/EET_CA1_Playground-CZ1212121218.p12', 'eet');
$dispatcher = new Dispatcher(PLAYGROUND_WSDL, $certificate);

$r = new Receipt();
$r->uuid_zpravy = UUID::v4();
$r->dic_popl = 'CZ1212121218';
$r->id_provoz = '181';
$r->id_pokl = '1';
$r->porad_cis = '1';
$r->dat_trzby = new \DateTime();
$r->celk_trzba = 1000;

echo $dispatcher->send($r); // FIK code should be returned
```

### License
MIT