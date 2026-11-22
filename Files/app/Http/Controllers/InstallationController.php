<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class InstallationController extends Controller
{
    //set database
    public function setDatabase(Request $request)
    {
        if (!file_exists(public_path('install.php'))) {
            abort(404);
        }
        $request->validate([
            'db_connection' => 'required',
            'db_host' => 'required',
            'db_port' => 'required',
            'db_database' => 'required',
            'db_username' => 'required',
            // 'db_password' => 'required' 
        ]);

        // Attempt to connect to the database
        // $connection = @mysqli_connect($request->db_host, $request->db_username, $request->db_password, $request->db_database, $request->db_port);



        // if (!$connection) {
        //     return response()->json(validationError(mysqli_connect_error()), 422);
        // }


        $db_set = [
            'DB_CONNECTION' => $request->db_connection,
            'DB_HOST' => $request->db_host,
            'DB_PORT' => $request->db_port,
            'DB_DATABASE' => $request->db_database,
            'DB_USERNAME' => $request->db_username,
            'DB_PASSWORD' => $request->db_password,

        ];

        foreach ($db_set as $key => $value) {
            updateEnvValue($key, $value);
        }


        // Test database connection
        // Set temporary database configuration
        $config = [
            'driver' => $request->input('db_connection'),
            'host' => $request->input('db_host'),
            'port' => $request->input('db_port'),
            'database' => $request->input('db_database'),
            'username' => $request->input('db_username'),
            'password' => $request->input('db_password', ''), // Default to empty if not provided
        ];

        Config::set('database.connections.testing', $config);

        try {
            // Attempt to connect using the temporary configuration
            DB::connection('testing')->getPdo();

            return response()->json([
                'success' => true,
                'message' => 'Database connection successful!',
            ]);
        } catch (\Exception $e) {
            return response()->json(validationError($e->getMessage()), 422);
        }

        // return response()->json(['message' => 'Connected Successfully']);
    }


    // import database
    public function importDatabase(Request $request)
    {
        if (!file_exists(public_path('install.php'))) {
            abort(404);
        }
        // re-wipe incase of a botched import
        //try wiping database
        // if (Artisan::call('db:wipe') !== 0) {
        //     return response()->json(validationError('Check database credentials'), 422);
        // }

         $exitCode = Artisan::call('db:wipe', [
            '--force' => true,
        ]);

        if ($exitCode !== 0) {
            return response()->json(validationError('Check database credentials'), 422);
        }
        $path = public_path('database.sql');
        $import = DB::unprepared(file_get_contents($path));

        //check if table exists after importing: This is to abort the installatio process if database import failed
        if (!Schema::hasTable('withdrawals')) {
            return response()->json(validationError('Database could not be imported'), 422);
        }

        // run migration for future tables
        Artisan::call('migrate');

        // do some cleaning
        //delete install.php
        File::delete(public_path('install.php'));


        // delete link
        if (File::isDirectory(public_path('storage'))) {
            File::deleteDirectory(public_path('storage'));
        }

        // create symlink
        Artisan::call('storage:link');

        // create folder
        $folders = [
            'profile',
            'kyc',
            'bots',
        ];

        foreach ($folders as $folder) {
            File::copyDirectory(storage_path('profiles'), storage_path('app/public/' . $folder));
            File::chmod(storage_path('app/public/' . $folder), 0775);
        }


        // clear caches
        Artisan::call('optimize:clear');


        // update environment
        $envs = [
            'APP_DEBUG' => 'false',
            'LOG_LEVEL' => "debug",
            'APP_URL' => url('/'),
            'DEMO_MODE' => 'false'
        ];

        foreach ($envs as $key => $value) {
            updateEnvValue($key, $value);
        }

        // truncate admin table first
        Admin::truncate();
        // update the admin login
        $admin = new  Admin();
        $admin->email = 'admin@admin.com';
        $admin->name = 'Admin';
        $admin->password = Hash::make('password');
        $admin->save();

        // Set the folder path
        $folderPath = storage_path('framework');

        // Validate if path exists and is a directory
        if (is_dir($folderPath)) {
            // Use RecursiveDirectoryIterator to efficiently traverse directories
            $directoryIterator = new \RecursiveDirectoryIterator(
                $folderPath,
                \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME
            );

            // Use RecursiveIteratorIterator to flatten the recursive structure
            $iterator = new \RecursiveIteratorIterator(
                $directoryIterator,
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $path) {
                // Only apply to directories, skip files
                if (is_dir($path)) {
                    try {
                        // Change permissions to 775 (Owner: rwx, Group: rwx, Others: r-x)
                        chmod($path, 0775);

                        // Optional: Log success if needed
                        // Log::info("Changed permissions for directory: $path");
                    } catch (\Exception $e) {
                        // Silently skip and continue with next directory
                        continue;
                    }
                }
            }
        }

        // regenerate key 
        Artisan::call('key:generate');
        return response()->json(['message' => 'Database Imported Successfully']);
    }


    // manual installation
    public function manualInstall(Request $request)
    {
        if (!file_exists(public_path('install.php'))) {
            abort(404);
        }

        //try wiping database
        // if (Artisan::call('db:wipe') !== 0) {
        //     return response()->json(validationError('Check database credentials'), 422);
        // }

        $exitCode = Artisan::call('db:wipe', [
            '--force' => true,
        ]);

        if ($exitCode !== 0) {
            return response()->json(validationError('Check database credentials'), 422);
        }

        $path = public_path('database.sql');
        $import = DB::unprepared(file_get_contents($path));

        //check if table exists after importing: This is to abort the installatio process if database import failed
        if (!Schema::hasTable('withdrawals')) {
            return response()->json(validationError('Database could not be imported'), 422);
        }

        // run migration for future tables
        Artisan::call('migrate');

        // do some cleaning
        //delete install.php
        File::delete(public_path('install.php'));


        // delete link
        if (File::isDirectory(public_path('storage'))) {
            File::deleteDirectory(public_path('storage'));
        }

        // create symlink
        Artisan::call('storage:link');

        // create folder
        $folders = [
            'profile',
            'kyc',
            'bots',
        ];

        foreach ($folders as $folder) {
            File::copyDirectory(storage_path('profiles'), storage_path('app/public/' . $folder));
            File::chmod(storage_path('app/public/' . $folder), 0775);
        }


        // clear caches
        Artisan::call('optimize:clear');


        // update environment
        $envs = [
            'APP_DEBUG' => 'false',
            'LOG_LEVEL' => "debug",
            'APP_URL' => url('/'),
            'DEMO_MODE' => 'false'
        ];

        foreach ($envs as $key => $value) {
            updateEnvValue($key, $value);
        }

        // truncate admin table first
        Admin::truncate();
        // update the admin login
        $admin = new  Admin();
        $admin->email = 'admin@admin.com';
        $admin->name = 'Admin';
        $admin->password = Hash::make('password');
        $admin->save();

        // regenerate key 
        Artisan::call('key:generate');
        $login_details = [
            'link' => route('admin.login'),
            'email' => 'admin@admin.com',
            'password' => 'password',
        ];
        $html = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Admin Login</title>
        </head>
        <body>
            <h1>Admin Login</h1>
            <p>To login, use the following credentials:</p>
            <p>Email: {$login_details['email']}</p>
            <p>Password: {$login_details['password']}</p>
            <p>Login URL: <a href='{$login_details['link']}'>{$login_details['link']}</a></p>
        </body>
        </html>";

        return response($html);
    }
}
