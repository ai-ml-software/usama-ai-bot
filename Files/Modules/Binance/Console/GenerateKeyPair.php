<?php

namespace Modules\Binance\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateKeyPair extends Command
{
    protected $signature = 'binance:keys-generate';
    protected $description = 'Generate RSA private and public key pair for Binance Plugin';

    public function handle()
    {

        $name = Str::orderedUuid();
        $name = str_replace('-', '_', $name);
        $path = storage_path("keys/binance");

        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $privateKeyPath = "$path/{$name}_private.pem";
        $publicKeyPath = "$path/{$name}_public.pem";

        // Generate private key
        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $privateKeyResource = openssl_pkey_new($config);

        if ($privateKeyResource === false) {
            $this->error("❌ OpenSSL failed to generate key pair.");
            while ($msg = openssl_error_string()) {
                $this->line("OpenSSL error: $msg");
            }
            return Command::FAILURE;
        }

        // Extract public key
        $keyDetails = openssl_pkey_get_details($privateKeyResource);
        $publicKey = $keyDetails['key'];

        // file_put_contents($privateKeyPath, $privateKey);
        file_put_contents($publicKeyPath, $publicKey);

        $this->info("✅ RSA keys generated successfully for Binance module!");
        $this->line("Private Key: $privateKeyPath");
        $this->line("Public Key:  $publicKeyPath");

        return Command::SUCCESS;
    }
}
