<?php

namespace Saulmoralespa\Wompi\Tests;

use Dotenv\Dotenv;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\Attributes\Depends;
use Saulmoralespa\Wompi\Client;
use PHPUnit\Framework\TestCase;

final class WompiTest extends TestCase
{
    public Client $wompi;

    protected function setUp(): void
    {
        $dotenv = Dotenv::createMutable(__DIR__ . '/../');
        $dotenv->load();

        $keyPrivate = $_ENV[ 'KEY_PRIVATE' ];
        $keyPublic = $_ENV[ 'KEY_PUBLIC' ];
        $keyIntegrety = $_ENV[ 'KEY_INTEGRETY' ];

        $this->wompi = new Client($keyPrivate, $keyPublic, $keyIntegrety);
        $this->wompi->sandbox();
    }

    public function testCardToken(): string
    {
        $data = [
            "number" => "4242424242424242",
            "exp_month" => "06",
            "exp_year" => "29",
            "cvc" => "123",
            "card_holder" => "Pedro Pérez"
        ];
        $response = $this->wompi->cardToken($data);
        $this->assertArrayHasKey('id', $response[ 'data' ]);
        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('data', $response);

        return $response[ 'data' ]['id'];
    }

    public function testNequiToken()
    {
        $data = [
            "phone_number" => "3178034732"
        ];
        $response = $this->wompi->nequiToken($data);
        $this->assertArrayHasKey('id', $response[ 'data' ]);
        $this->assertArrayHasKey('status', $response[ 'data' ]);

        return $response[ 'data' ]['id'];
    }

    public function testDaviplataToken()
    {
        $data = [
            "type_document" => "CC",
            "number_document" => "1122233",
            "product_number" => "3991111111"
        ];

        $response = $this->wompi->daviplataToken($data);
        $this->assertArrayHasKey('id', $response[ 'data' ]);
        $this->assertArrayHasKey('status', $response[ 'data' ]);

        return $response[ 'data' ]['id'];
    }

    /**
     * @throws GuzzleException
     */
    public function testBancolombiaToken(): string
    {
        $data = [
            "type_document" => "CC",
            "number_document" => "1122233",
            "product_number" => "3991111111",
            "redirect_url" => "https://www.bancolombia.com",
            "type_auth" => "TOKEN"
        ];

        $response = $this->wompi->bancolombiaToken($data);
        $this->assertArrayHasKey('id', $response[ 'data' ]);
        $this->assertArrayHasKey('status', $response[ 'data' ]);
        $this->assertEquals('PENDING', $response[ 'data' ][ 'status' ]);
        $this->assertIsString($response[ 'data' ][ 'authorization_url' ]);

        return $response[ 'data' ]['id'];
    }

    #[Depends('testNequiToken')]
    public function testGetStatusSubscriptionNequi(string $token)
    {
        $response = $this->wompi->getStatusSubscriptionNequi($token);
        $this->assertArrayHasKey('id', $response[ 'data' ]);
        $this->assertArrayHasKey('status', $response[ 'data' ]);
    }

    #[Depends('testDaviplataToken')]
    public function testGetStatusSubscriptionDaviplata(string $token)
    {
        $response = $this->wompi->getStatusSubscriptionDaviplata($token);
        $this->assertArrayHasKey('id', $response[ 'data' ]);
        $this->assertArrayHasKey('status', $response[ 'data' ]);
    }

    #[Depends('testBancolombiaToken')]
    public function testGetStatusSubscriptionBancolombia(string $token)
    {
        $response = $this->wompi->getStatusSubscriptionBancolombia($token);
        $this->assertArrayHasKey('status', $response[ 'data' ]);
        $this->assertIsString($response[ 'data' ][ 'authorization_url' ]);
    }

    public function testGetAcceptanceTokens(): array
    {
        $response = $this->wompi->getAcceptanceTokens();
        $this->assertArrayHasKey('presigned_acceptance', $response[ 'data' ]);
        $this->assertArrayHasKey('presigned_personal_data_auth', $response[ 'data' ]);

        return $response;
    }

    #[Depends('testCardToken')]
    public function testCreateSourceCard(string $token)
    {
        $type = "CARD"; //NEQUI, CARD, DAVIPLATA, BANCOLOMBIA_TRANSFER
        $acceptanceTokens = $this->wompi->getAcceptanceTokens();
        $data = $this->getDataSource($type, $token, $acceptanceTokens[ 'data' ]);

        $response = $this->wompi->createSource($data);
        $this->assertArrayHasKey('id', $response[ 'data' ]);
        $this->assertArrayHasKey('type', $response[ 'data' ]);
        $this->assertEquals('AVAILABLE', $response[ 'data' ][ 'status' ]);

        return $response[ 'data' ]['id'];
    }

    #[Depends('testNequiToken')]
    public function testCreateSourceNequi(string $token)
    {
        $type = "NEQUI"; //NEQUI, CARD, DAVIPLATA, BANCOLOMBIA_TRANSFER
        $acceptanceTokens = $this->wompi->getAcceptanceTokens();
        $data = $this->getDataSource($type, $token, $acceptanceTokens[ 'data' ]);

        $response = $this->wompi->createSource($data);
        $this->assertArrayHasKey('id', $response[ 'data' ]);
        $this->assertArrayHasKey('type', $response[ 'data' ]);
        $this->assertEquals('AVAILABLE', $response[ 'data' ][ 'status' ]);

        return $response[ 'data' ]['id'];
    }

    #[Depends('testBancolombiaToken')]
    #[Depends('testGetAcceptanceTokens')]
    public function testCreateSourceBancolombia(string $token, array $acceptanceTokens)
    {
        $type = "BANCOLOMBIA_TRANSFER"; //NEQUI, CARD, DAVIPLATA, BANCOLOMBIA_TRANSFER
        $data = $this->getDataSource($type, $token, $acceptanceTokens[ 'data' ]);
        $exceptionText = "La fuente de pago sigue en estado pendiente";

        try {
            $this->wompi->createSource($data);
        } catch (\Exception $exception) {
            $this->assertStringContainsString($exceptionText, $exception->getMessage());
        }
    }

    #[Depends('testCreateSourceCard')]
    public function testCancelSubscription(int $sourceId)
    {
        $exceptionText = "Únicamente se pueden anular fuentes de pago con el tipo de operación financiera 'PREAUTHORIZATION'";

        try {
            $this->wompi->cancelSubscription($sourceId);
        } catch (\Exception $exception) {
            $this->assertStringContainsString($exceptionText, $exception->getMessage());
        }
    }

    #[Depends('testCreateSourceCard')]
    public function testTransactionCard(int $sourceId)
    {
        $data = [
            "amount_in_cents" => 4990000,
            "currency" => "COP",
            "customer_email" => "example@gmail.com",
            "payment_method" =>  [
                "installments" => 1
            ],
            "reference" => (string)time(),
            "payment_source_id" => $sourceId,
            "recurrent" => true // Recurrente
        ];

        $response = $this->wompi->transaction($data);
        $this->assertArrayHasKey('id', $response[ 'data' ]);
        $this->assertArrayHasKey('status', $response[ 'data' ]);
    }

    #[Depends('testCreateSourceNequi')]
    public function testTransactionNequi(int $sourceId)
    {
        $data = [
            "amount_in_cents" => 6000000,
            "currency" => "COP",
            "customer_email" => "example@gmail.com",
            "reference" => (string)time(),
            "payment_source_id" => $sourceId,
        ];

        $response = $this->wompi->transaction($data);
        $this->assertArrayHasKey('id', $response[ 'data' ]);
        $this->assertArrayHasKey('status', $response[ 'data' ]);

        return $response[ 'data' ][ 'id' ];
    }

    #[Depends('testTransactionNequi')]
    public function testGetTransaction(string $transactionId)
    {
        $response = $this->wompi->getTransaction($transactionId);
        $this->assertArrayHasKey('status', $response[ 'data' ]);
        $this->assertArrayHasKey('payment_method', $response[ 'data' ]);
        $this->assertIsArray($response[ 'data' ]['payment_method']);
    }

    private function getDataSource(string $type, string $token, $data): array
    {
        return [
            "customer_email" => "testuser@domain.com",
            "type" => $type,
            "token" => $token,
            "payment_description" => "Descripción de la suscripción creada",
            "acceptance_token" => $data[ "presigned_acceptance" ][ "acceptance_token" ],
            "accept_personal_auth" => $data[ "presigned_personal_data_auth" ][ "acceptance_token" ]
        ];
    }

    public function testCreatePaymentLink()
    {
        $data = [
            "name" => "Pago de arriendo edificio Lombardía - AP 505",
            "description" => "Arriendo mensual", // Descripción del pago
            "single_use" => false, // `false` current caso de que el link de pago pueda recibir múltiples transacciones APROBADAS o `true` si debe dejar de aceptar transacciones después del primer pago APROBADO
            "collect_shipping" => false, // Si deseas que el cliente inserte su información de envío current el checkout, o no
            "currency" => "COP",
            "amount_in_cents" => 500000
        ];
        $response = $this->wompi->createPaymentLink($data);
        $this->assertArrayHasKey('id', $response[ 'data' ]);
        $this->assertArrayHasKey('name', $response[ 'data' ]);

        return $response[ 'data' ][ 'id' ];
    }

    #[Depends('testCreatePaymentLink')]
    public function testGetPaymentLink(string $id)
    {
        $response = $this->wompi->getPaymentLink($id);
        $this->assertArrayHasKey('name', $response[ 'data' ]);
        $this->assertArrayHasKey('currency', $response[ 'data' ]);
    }
}