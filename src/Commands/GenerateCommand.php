<?php

namespace LaravelOpenApi\Commands;

use Illuminate\Console\Command;
use LaravelOpenApi\Generators\OpenApiGenerator;
use Illuminate\Support\Facades\File; 
use Symfony\Component\Yaml\Yaml; // Add this import

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
        
        // Adjust output filename extension based on format, if not already part of $output option
        $outputExtension = pathinfo($output, PATHINFO_EXTENSION);

        if (empty($outputExtension)) { // If --output doesn't have an extension, append based on format
            $output .= '.' . $format;
        } elseif (strtolower($outputExtension) !== $format) { // Use strtolower for comparison
            $this->warn("Output filename extension .{$outputExtension} does not match format --format={$format}. Using format {$format} with original filename.");
            // Optionally, force extension:
            // $output = pathinfo($output, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . pathinfo($output, PATHINFO_FILENAME) . '.' . $format;
        }

        $content = '';

        if ($format === 'yaml') {
            if (!class_exists(Yaml::class)) {
                $this->error('symfony/yaml package is required for YAML output. Please install it: composer require symfony/yaml');
                return Command::FAILURE;
            }
            try {
                // DUMP_OBJECT_AS_MAP is good for OpenAPI objects.
                // DUMP_MULTI_LINE_LITERAL_BLOCK improves readability for descriptions.
                $content = Yaml::dump($spec, 4, 2, Yaml::DUMP_OBJECT_AS_MAP | Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
            } catch (\Throwable $e) {
                $this->error('Failed to generate YAML: ' . $e->getMessage());
                return Command::FAILURE;
            }
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
