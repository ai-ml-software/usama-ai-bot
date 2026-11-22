<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TemplateController extends Controller
{
    //define variables
    private $file_path;
    private $folder_path;

    public function __construct()
    {
        $this->file_path = base_path('template.zip');
        $this->folder_path = base_path();
    }

    //index
    public function index()
    {
        $page_title = "Template Manager";
        $local_templates = getTemplates();
        $remote_templates = [];
        $url = endpoint('templates/info');
        $httpHost = domain();

        try {
            $response = Http::withHeaders([
                'X-DOMAIN' => $httpHost
            ])->get($url);

            if ($response->successful()) {
                $response_data = json_decode($response->body());
                $remote_templates = collect($response_data)->sortByDesc(fn($t) => $t->name == site('template'));
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

        // dd($remote_templates);

        return view('admin.settings.templates.index', compact(
            'page_title',
            'local_templates',
            'remote_templates'
        ));
    }

    // return download index
    public function download(Request $request)
    {
        $page_title = 'Template Downloader';
        $template = $request->template;
        $version = $request->version;

        return view('admin.settings.templates.download', compact(
            'page_title',
            'template',
            'version'
        ));
    }

    // check for valid template
    public function checkTemplate(Request $request)
    {
        $url = endpoint('templates/info');
        $httpHost = domain();
        $request->validate([
            'template' => 'required',
            'version' => 'required'
        ]);

        try {
            $response = Http::withHeaders([
                'X-DOMAIN' => $httpHost
            ])
                ->withQueryParameters([
                    'template' => $request->template,
                    'version' => $request->version,
                ])
                ->get($url);

            if ($response->successful()) {
                $response_data = json_decode($response->body());
                if ($response_data->name) {
                    return response()->json(['message' => 'Valid template detected. Attempting to download v' .  $request->version . '...']);
                }
            }
        } catch (\Throwable $th) {
            return response()->json(validationError('Error requesting template'), 422);
        }

        return response()->json(validationError('Invalid template'), 422);
    }

    // download the file
    public function downloadTemplate(Request $request)
    {
        $domain = domain();
        $url = endpoint('templates/download');

        $response = Http::withHeaders([
            'X-DOMAIN' => domain(),
            'X-TEMPLATE' => $request->template
        ])->timeout(5000)->get($url);
        if ($response->status() == 200) {
            File::put($this->file_path, $response->body());

            // change permission
            // File::chmod($this->folder_path, 0775);
            return response()->json(['message' => 'Template downloaded successfully. Attempting to extract...']);
        }
        return response()->json(validationError('Failed to download template'), 422);
    }

    // extract
    public function extractTemplate(Request $request)
    {
        // Check if the zip file exists
        if (file_exists($this->file_path)) {
            // Create a new ZipArchive instance
            $zip = new \ZipArchive;

            // Open the zip file
            if ($zip->open($this->file_path) === TRUE) {
                // Extract the contents to the specified folder
                $zip->extractTo($this->folder_path);

                // Close the zip file
                $zip->close();


                // copy blades
                $template = $request->template;
                File::copyDirectory(base_path($template . '/blades/' . $template), resource_path('views/templates/' . $template));

                // copy assets
                File::copyDirectory(base_path($template . '/assets/' . $template), public_path('assets/templates/' . $template));

                // copy config
                File::copy(base_path($template . '/config.json'), resource_path('views/templates/' . $template . '/config.json'));

                return response()->json(['message' => 'Template extracted successfully. Atempting to setup...']);
            } else {
                return response()->json(validationError('Failed to extract template'), 422);
            }
        } else {
            return response()->json(validationError('Template file archive not found'), 422);
        }
    }


    public function sortTemplate(Request $request)
    {
        // delete the extracted folder
        $template = $request->template;
        File::deleteDirectory(base_path($template));

        // delete the zip
        File::delete(base_path('template.zip'));
        // set the template in .env
        updateEnvValue('TEMPLATE', $template);
        // set template to settings
        updateSite(['template' => $template]);
        // update folder permission
        // clear cache
        Artisan::call('optimize:clear');

        return response()->json(['message' => 'Template installed successfully']);
    }


    // Activate template 
    public function activateTemplate(Request $request)
    {
        $request->validate([
            'template' => 'required',
        ]);

        $template = $request->template;

        // check if the template is valid
        $local_templates = getTemplates();
        if (!in_array($template, $local_templates)) {
            return response()->json(validationError('Invalid template'), 422);
        }

        // update in .env 
        updateEnvValue('TEMPLATE', $template);
        updateSite(['template' => $template]);
        return response()->json(['message' => 'Template updated successfully']);
    }


    // delete template
    public function deleteTemplate(Request $request)
    {
        $request->validate([
            'template' => 'required'
        ]);

        $template = $request->template;
        // set active template to default
        updateSite(['template' => 'default']);
        updateEnvValue('TEMPLATE', 'default');
        // define aseset and blade folder
        $folders = [
            resource_path('views/templates/' . $template),
            public_path('assets/templates/' . $template),
        ];

        // delete if exists
        $delete_error = null;
        foreach ($folders as $folder) {
            if (File::isDirectory($folder)) {
                try {
                    File::deleteDirectory($folder);
                } catch (\Throwable $th) {
                    //throw $th;
                    $delete_error = true;
                }
            }
        }

        if ($delete_error) {
            return response()->json(validationError('Error Deleting template'), 422);
        }

        return response()->json(['message' => "Template deleted successfully"]);
    }
}
