<?php

namespace LaravelOpenApi\Commands;

use Illuminate\Console\Command;
use LaravelOpenApi\Generators\OpenApiGenerator;
use Illuminate\Support\Facades\File; // Using Laravel's File facade

class GenerateCommand extends Command
{
    protected $signature = 'openapi:generate 
                           {--output=public/openapi.json : Output file path}
                           {--format=json : Output format (json|yaml)}';
                           // Note: Removed --routes and --exclude from original spec for now, can be added later.

    protected $description = 'Generate OpenAPI specification document';

    public function handle(OpenApiGenerator $generator): int
    {
        $this->info('Generating OpenAPI specification...');

        $spec = $generator->generate();
        
        $output = $this->option('output');
        $format = strtolower($this->option('format'));
        $content = '';

        if ($format === 'yaml') {
            $this->error('YAML output is not yet implemented. Please use format=json.');
            // For future:
            // try {
            //     if (!class_exists(\Symfony\Component\Yaml\Yaml::class)) {
            //         $this->error('symfony/yaml package is required for YAML output. Please install it: composer require symfony/yaml');
            //         return Command::FAILURE;
            //     }
            //     $content = \Symfony\Component\Yaml\Yaml::dump($spec, 4, 2, \Symfony\Component\Yaml\Yaml::DUMP_OBJECT_AS_MAP);
            // } catch (\Throwable $e) {
            //     $this->error('Failed to generate YAML: ' . $e->getMessage());
            //     return Command::FAILURE;
            // }
            return Command::FAILURE; // Remove this once YAML is implemented
        } elseif ($format === 'json') {
            $content = json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Failed to encode JSON: ' . json_last_error_msg());
                return Command::FAILURE;
            }
        } else {
            $this->error("Unsupported format: {$format}. Please use 'json' or 'yaml'.");
            return Command::FAILURE;
        }

        try {
            // Ensure directory exists
            $directory = dirname($output);
            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true, true); // Recursive, force
            }

            File::put($output, $content);
        } catch (\Throwable $e) {
            $this->error('Failed to write OpenAPI specification to file: ' . $e->getMessage());
            $this->error('Attempted path: ' . realpath(dirname($output)) . '/' . basename($output));
            return Command::FAILURE;
        }
        
        $this->info("OpenAPI specification generated successfully: {$output}");
        return Command::SUCCESS;
    }
}
