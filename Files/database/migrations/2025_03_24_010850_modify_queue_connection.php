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
        // Get the path to the .env file
        $envPath = base_path('.env');

        // Check if the file exists
        if (file_exists($envPath)) {
            // Read the content of the .env file
            $envContent = file_get_contents($envPath);

            // Regular expression to match QUEUE_CONNECTION lines
            $pattern = '/^QUEUE_CONNECTION=.*$/m';

            // Find all matches
            preg_match_all($pattern, $envContent, $matches);

            // Remove all QUEUE_CONNECTION entries
            $envContent = preg_replace($pattern, '', $envContent);

            // Remove extra empty lines
            $envContent = preg_replace('/\n+/', "\n", $envContent);

            // Add the QUEUE_CONNECTION with database value
            $envContent .= "\nQUEUE_CONNECTION=database";

            // Write the updated content back to the .env file
            file_put_contents($envPath, $envContent);

            // Output a message
            // if (count($matches[0]) > 1) {
            //     echo "Duplicate QUEUE_CONNECTION entries removed and set to 'database'.\n";
            // } else {
            //     echo "QUEUE_CONNECTION set to 'database'.\n";
            // }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
