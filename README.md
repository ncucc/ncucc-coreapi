# NCUCC CoreAPI Client

ncucc-coreapi is a PHP HTTP client

```bash
php composer.phar require linuzilla/ncucc-coreapi
```

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use NCUCC\CoreAPI\Client;
use NCUCC\CoreAPI\APIException;

$config_file = "config.json";
$config = json_decode(file_get_contents($config_file));

$coreapi = new Client($config);

try {
	$coreapi->some_api_function();
} catch (NCUCC\CoreAPI\APIException $e) {
	echo 'Caught exception: ',  $e->getMessage(), "\n";
}
```

```json
{
    "base": "// API base URL",
    "appkey": "your app key",
    "magickey": "your magic key",
    "secret": "secret",
    "publicKey": "public key in pem format",
    "token": "access token",
    "apiMetaFile": "api meta file"
}
```
