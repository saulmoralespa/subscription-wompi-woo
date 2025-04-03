Wompi API PHP SDK
==================

[Documentation Wompi](https://docs.wompi.co/docs/colombia/ambientes-y-llaves/)

## Installation

You will need at least PHP 8.1. We match [officially supported](https://www.php.net/supported-versions.php) versions of PHP.

Use [composer](https://getcomposer.org/) package manager to install the lastest version of the package:

```shell
composer require saulmoralespa/wompi-api-php dev-main
```

```php
// ... please, add composer autoloader first
include_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

//import Client
use Saulmoralespa\Wompi\Client;

$keyPrivate = "your_keyprivate";
$keyPublic = "your_keypublic";
$keyIntegrety = "your_keyIntegrety";

//instance class Client

$wompi = new Client($keyPrivate, $keyPublic, $keyIntegrety);

//for sandbox
$wompi->sandbox();
```

### Create card token

```php
$data = [
    "number" => "4242424242424242",
    "exp_month" => "06",
    "exp_year" => "29",
    "cvc" => "123",
    "card_holder" => "Pedro Pérez"
];

try {
$response = $wompi->cardToken($data);
$token = $response[ 'data' ]['id'];
} catch(\Exception $exception) {

}
```

### Create Nequi token

```php
$data = [
    "phone_number" => "3178034732"
];

try {
$response = $wompi->nequiToken($data);
$token = $response[ 'data' ]['id'];
} catch(\Exception $exception) {

}
```

### Create Daviplata token

```php
$data = [
    "type_document" => "CC",
    "number_document" => "1122233",
    "product_number" => "3991111111"
];

try {
$response = $this->wompi->daviplataToken($data);
$token = $response[ 'data' ]['id'];
} catch(\Exception $exception) {

}
```

### Create Bancolombia token

```php
$data = [
    "type_document" => "CC",
    "number_document" => "1122233",
    "product_number" => "3991111111",
    "redirect_url" => "https://www.bancolombia.com",
    "type_auth" => "TOKEN" // or TRANSACTION
];

try {
$response = $this->wompi->bancolombiaToken($data);
$token = $response[ 'data' ]['id'];
} catch(\Exception $exception) {

}
```

### Get Status Subscription Nequi

```php
try {
$token = "";
$response = $this->wompi->getStatusSubscriptionNequi($token);
} catch (\Exception $exception) {

}
```

### Get Status Subscription Daviplata

```php
try {
$token = "";
$response = $this->wompi->getStatusSubscriptionDaviplata($token);
} catch (\Exception $exception) {

}
```

### Get Status Subscription Bancolombia

```php
try {
$token = "";
$response = $this->wompi->getStatusSubscriptionBancolombia($token);
} catch (\Exception $exception) {

}
```

### Create Source Card

```php
try {
$type = "CARD"; //NEQUI, CARD, DAVIPLATA, BANCOLOMBIA_TRANSFER
$token = "";
$acceptanceTokens = $this->wompi->getAcceptanceTokens();
$data = [
    "customer_email" => "testuser@domain.com",
    "type" => $type,
    "token" => $token,
    "payment_description" => "Descripción de la suscripción creada",
    "acceptance_token" => $acceptanceTokens[ 'data' ][ "presigned_acceptance" ][ "acceptance_token" ],
    "accept_personal_auth" => $acceptanceTokens[ 'data' ][ "presigned_personal_data_auth" ][ "acceptance_token" ]
];
$response = $this->wompi->createSource($data);
} catch (\Exception $exception) {

}
```

### Create Transaction with Card

```php
try {
$sourceId = 12344;
$data = [
    "amount_in_cents" => 4990000,
    "currency" => "COP",
    "customer_email" => "example@gmail.com",
    "payment_method" =>  [
        "installments" => 1
    ],
    "reference" => (string)time(),
    "payment_source_id" => $sourceId,
    "recurrent" => true // optional
];
$response = $this->wompi->transaction($data);
} catch (\Exception $exception) {

}
```

### Create Transaction with Nequi

```php
try {
$sourceId = 13345;
$data = [
    "amount_in_cents" => 6000000,
    "currency" => "COP",
    "customer_email" => "example@gmail.com",
    "reference" => (string)time(),
    "payment_source_id" => $sourceId
];

$response = $this->wompi->transaction($data);
} catch (\Exception $exception) {

}
```

### Create Payment Link
```php
try {
$data = [
    "name" => "Pago de arriendo edificio Lombardía - AP 505",
    "description" => "Arriendo mensual", // Descripción del pago
    "single_use" => false, // `false` current caso de que el link de pago pueda recibir múltiples transacciones APROBADAS o `true` si debe dejar de aceptar transacciones después del primer pago APROBADO
    "collect_shipping" => false, // Si deseas que el cliente inserte su información de envío current el checkout, o no
    "currency" => "COP",
    "amount_in_cents" => 500000
];
$response = $this->wompi->createPaymentLink($data);
} catch (\Exception $exception) {

}
```

### Get Payment Link
```php
try {
$id = "ID_PAYMENT_LINK";
$response = $this->wompi->getPaymentLink($id);
} catch (\Exception $exception) {

}
```