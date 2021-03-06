# PHP Library for Czech EET system

## Installation
Install superfaktura/eet using  [Composer](http://getcomposer.org/):

```sh
$ composer require superfaktura/eet
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
use Po1nt\EET\FileCertificate;
use Po1nt\EET\Receipt;

$certificate = new FileCertificate(DIR_CERT . '/EET_CA1_Playground-CZ1212121218.p12', 'eet');
$dispatcher = new Dispatcher(PLAYGROUND_WSDL, $certificate);

$r = new Receipt();
$r->uuid_zpravy = 'b3a09b52-7c87-4014-a496-4c7a53cf9120';
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