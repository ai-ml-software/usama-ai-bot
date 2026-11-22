<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $envPath = base_path('.env');

        $lines = file($envPath, FILE_IGNORE_NEW_LINES);
        $seen = [];
        $cleaned = [];

        foreach ($lines as $line) {
            $trim = trim($line);

            // Preserve comments and empty lines
            if ($trim === '' || str_starts_with($trim, '#')) {
                $cleaned[] = $line;
                continue;
            }

            // Detect key=value pairs
            if (strpos($trim, '=') !== false) {
                [$key] = explode('=', $trim, 2);

                // Skip if duplicate key
                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
            }

            $cleaned[] = $line;
        }

        file_put_contents($envPath, implode(PHP_EOL, $cleaned) . PHP_EOL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
