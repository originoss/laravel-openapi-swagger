<?php

namespace LaravelOpenApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

class SpecController extends Controller
{
    private function getSpecPath(string $format = 'json'): string
    {
        $fileName = config('openapi.paths.output_filename', 'openapi') . '.' . $format;
        return public_path(config('openapi.paths.output_directory', '') . DIRECTORY_SEPARATOR . $fileName);
    }

    public function json(): JsonResponse
    {
        $filePath = $this->getSpecPath('json');

        if (!File::exists($filePath)) {
            return response()->json(['error' => 'OpenAPI specification file not found. Please generate it first.'], 404);
        }

        $content = File::get($filePath);
        $decoded = json_decode($content);

        if (json_last_error() !== JSON_ERROR_NONE) {
             return response()->json(['error' => 'Invalid JSON format in the OpenAPI specification file: ' . json_last_error_msg()], 500);
        }

        return response()->json($decoded);
    }

    public function yaml(): Response
    {
        $filePath = $this->getSpecPath('yaml');

        if (!File::exists($filePath)) {
            return response('OpenAPI YAML specification file not found. Please generate it first.', 404)
                    ->header('Content-Type', 'text/plain');
        }
        
        $content = File::get($filePath);

        if (!class_exists(Yaml::class)) {
            if (app()->environment('local', 'development', 'testing')) {
                logger()->warning('Cannot validate YAML: symfony/yaml package not found. Install it with: composer require symfony/yaml');
            }
        } else {
            try {
                Yaml::parse($content); 
            } catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
                return response('Invalid YAML format: ' . $e->getMessage(), 500)
                        ->header('Content-Type', 'text/plain');
            }
        }

        return response($content, 200)
                ->header('Content-Type', 'application/yaml')
                ->header('Access-Control-Allow-Origin', '*');
    }

    public function ui(): \Illuminate\Contracts\View\View
    {
        $viewName = config('openapi.ui.view', 'openapi::swagger-ui');
        
        $specUrlJson = route(config('openapi.ui.spec_route_name_json', 'openapi.json'));
        $specUrlYaml = route(config('openapi.paths.yaml_route_name', 'openapi.yaml'));
        
        $defaultFormat = config('openapi.ui.default_format', 'json');
        
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
