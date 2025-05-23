<?php

namespace LaravelOpenApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

class SpecController extends Controller
{
    // Default path to the OpenAPI spec file, relative to public_path() or as defined in config.
    // Consider making this configurable via config('openapi.generation.output_path') or similar.
    private function getSpecPath(string $format = 'json'): string
    {
        // Assuming the GenerateCommand defaults to public/openapi.json or public/openapi.yaml
        // This should ideally align with the output path used in GenerateCommand or be configurable.
        // For now, let's use a path relative to public_path()
        $fileName = config('openapi.paths.output_filename', 'openapi') . '.' . $format; // Default 'openapi.json' or 'openapi.yaml'
        return public_path(config('openapi.paths.output_directory', '') . DIRECTORY_SEPARATOR . $fileName);
    }

    public function json(): JsonResponse
    {
        $filePath = $this->getSpecPath('json');

        if (!File::exists($filePath)) {
            return response()->json(['error' => 'OpenAPI specification file not found. Please generate it first.'], 404);
        }

        // Try to decode and re-encode to validate JSON and ensure consistent formatting.
        // Alternatively, just return File::get($filePath) with appropriate headers.
        $content = File::get($filePath);
        $decoded = json_decode($content);

        if (json_last_error() !== JSON_ERROR_NONE) {
             return response()->json(['error' => 'Invalid JSON format in the OpenAPI specification file: ' . json_last_error_msg()], 500);
        }

        return response()->json($decoded); // Serve the decoded and re-encoded JSON
    }

    public function yaml(): Response
    {
        $filePath = $this->getSpecPath('yaml');

        if (!File::exists($filePath)) {
            return response('OpenAPI YAML specification file not found. Please generate it first.', 404)
                    ->header('Content-Type', 'text/plain');
        }
        
        // Get the YAML content
        $content = File::get($filePath);

        // Validate the YAML if the Symfony YAML component is available
        if (!class_exists(Yaml::class)) {
            // Still serve the content, but log a warning that validation is skipped
            if (app()->environment('local', 'development', 'testing')) {
                logger()->warning('Cannot validate YAML: symfony/yaml package not found. Install it with: composer require symfony/yaml');
            }
        } else {
            try {
                // Try to parse to validate
                Yaml::parse($content); 
            } catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
                return response('Invalid YAML format: ' . $e->getMessage(), 500)
                        ->header('Content-Type', 'text/plain');
            }
        }

        // Return the YAML content with the appropriate content type
        return response($content, 200)
                ->header('Content-Type', 'application/yaml')
                ->header('Access-Control-Allow-Origin', '*'); // Allow cross-origin requests for Swagger UI
    }

    public function ui(): \Illuminate\Contracts\View\View
    {
        $viewName = config('openapi.ui.view', 'openapi::swagger-ui');
        
        // Get configuration for both JSON and YAML formats
        $specUrlJson = route(config('openapi.ui.spec_route_name_json', 'openapi.json'));
        $specUrlYaml = route(config('openapi.paths.yaml_route_name', 'openapi.yaml'));
        
        // Get preferred format from config
        $defaultFormat = config('openapi.ui.default_format', 'json');
        
        // Allow passing additional Swagger UI config from openapi.php
        $swaggerUiConfig = config('openapi.ui.config', []);

        return view($viewName, [
            'title' => config('openapi.ui.title', 'OpenAPI Documentation'),
            'specUrlJson' => $specUrlJson,
            'specUrlYaml' => $specUrlYaml,
            'defaultFormat' => $defaultFormat,
            'swaggerUiConfig' => $swaggerUiConfig,
        ]);
    }
}
