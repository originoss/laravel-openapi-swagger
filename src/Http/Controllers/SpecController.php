<?php

namespace LaravelOpenApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response; // For YAML content type
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File; // Or native file_get_contents
// use Symfony\Component\Yaml\Yaml; // If YAML support is desired - keep commented for now

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

    public function yaml() // Return type hint : Response (Illuminate\Http\Response)
    {
        $filePath = $this->getSpecPath('yaml');

        if (!File::exists($filePath)) {
            return response('OpenAPI YAML specification file not found. Please generate it first.', 404)
                    ->header('Content-Type', 'text/plain');
        }
        
        // For YAML, just return the content directly with the correct content type.
        // No need to parse and re-dump usually, unless validation is desired.
        $content = File::get($filePath);

        // Basic check if it might be YAML (optional)
        // if (!class_exists(Yaml::class)) {
        //     return response('Cannot serve YAML: symfony/yaml package not found.', 500)->header('Content-Type', 'text/plain');
        // }
        // try {
        //     Yaml::parse($content); // Try to parse to validate
        // } catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
        //     return response('Invalid YAML format: ' . $e->getMessage(), 500)->header('Content-Type', 'text/plain');
        // }

        return response($content, 200)->header('Content-Type', 'application/yaml'); 
        // Or 'text/yaml', 'application/x-yaml'. 'application/yaml' is common.
    }
}
