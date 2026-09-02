<?php

namespace App\Console\Commands\GoogleMerchant;

use App\Support\GoogleMerchant\GoogleMerchantClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('google-merchant:register {email : Correo humano que recibirá avisos técnicos de Google}')]
#[Description('Registra una sola vez el proyecto de Google Cloud ante Merchant API.')]
class RegisterGoogleMerchantDeveloperCommand extends Command
{
    public function handle(GoogleMerchantClient $client): int
    {
        try {
            $client->assertConfigured(requireDataSource: false);
            $client->registerDeveloper((string) $this->argument('email'));
            $dataSources = $client->dataSources();
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Proyecto de Google Cloud registrado correctamente en Merchant Center.');

        if ($dataSources !== []) {
            $this->table(
                ['ID', 'Nombre', 'Entrada'],
                array_map(fn (array $source): array => [
                    $source['dataSourceId'] ?? '',
                    $source['displayName'] ?? '',
                    $source['input'] ?? '',
                ], $dataSources),
            );
        }

        return self::SUCCESS;
    }
}
