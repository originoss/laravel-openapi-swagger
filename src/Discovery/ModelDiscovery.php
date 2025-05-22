<?php

namespace LaravelOpenApi\Discovery;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use ReflectionProperty; // Add this import
use LaravelOpenApi\Parsers\AttributeParser; // Add this import
use Symfony\Component\Finder\Finder;

class ModelDiscovery
{
    public function __construct(
        private AttributeParser $attributeParser, // Inject AttributeParser
        private array $config // Keep existing config from previous version for now
    ) {}

    public function discoverModels(array $directories = []): Collection
    {
        if (empty($directories)) {
            // Use config if available, otherwise default to app_path('Models')
            $configuredDirectories = $this->config['directories'] ?? [app_path('Models')];
            $directories = $configuredDirectories;
        }

        return collect($directories)
            ->flatMap(fn(string $dir) => $this->scanDirectory($dir))
            ->filter(fn(string $class) => $this->isValidModelClass($class))
            ->map(fn(string $class) => $this->analyzeModel($class))
            ->values();
    }

    private function scanDirectory(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $finder = new Finder();
        $files = $finder->files()->in($directory)->name('*.php');
        $classes = [];
        
        // Determine the base namespace for models.
        // Assumes 'Models' directory is directly under 'app/', and namespace is App\Models.
        // This might need to be more flexible depending on project structure.
        $appNamespace = rtrim(app()->getNamespace(), '\\'); // Typically "App"
        
        // We need to calculate the part of the namespace that corresponds to the subdirectory structure *within* one of the configured model directories.
        // Example: if $directory is "/var/www/html/app/Models/Business" and app_path() is "/var/www/html/app"
        // We want to get "Models\Business" from this.
        
        $baseDirForNamespaceCalc = '';
        // Try to find which configured base directory $directory is under
        // This is naive, assumes configured directories are not nested.
        // A better approach would be to pass the base path of the "models root" (e.g. app_path('Models'))
        // For now, let's assume $directory is something like app_path('Models') or app_path('Models/SubDir')
        if (str_starts_with($directory, app_path())) {
            $baseDirForNamespaceCalc = app_path();
        } else {
            // Fallback or error, this part is tricky if directories are outside app_path but still part of app's namespace
            // For now, we'll proceed assuming it's within app_path for simplicity of relative path calculation.
            $baseDirForNamespaceCalc = app_path(); 
        }

        foreach ($files as $file) {
            // Get the path of the file relative to the $baseDirForNamespaceCalc (e.g., "Models/User.php" or "Models/Sub/Order.php")
            $relativePath = ltrim(str_replace($baseDirForNamespaceCalc, '', $file->getRealPath()), DIRECTORY_SEPARATOR);
            
            // Construct the class name from the relative path
            // e.g., "Models\User" from "Models/User.php"
            // e.g., "Models\Sub\Order" from "Models/Sub/Order.php"
            $classNamespacePart = str_replace(['.php', DIRECTORY_SEPARATOR], ['', '\\'], $relativePath);
            
            $fqcn = $appNamespace . '\\' . $classNamespacePart;
            
            $classes[] = $fqcn;
        }
        
        return $classes;
    }

    private function isValidModelClass(string $classCandidate): bool
    {
        if (!class_exists($classCandidate)) {
            return false;
        }
        
        $reflection = new ReflectionClass($classCandidate);

        return $reflection->isSubclassOf(Model::class) && 
               !$reflection->isAbstract() &&
               $reflection->isInstantiable();
    }

    private function analyzeModel(string $modelClass): ModelSchema
    {
        $reflectionClass = new ReflectionClass($modelClass);
        /** @var Model $instance */
        $instance = app($modelClass);

        $classAttributes = $this->attributeParser->getClassAttributes($reflectionClass);
        
        $propertyAttributes = [];
        // Get public and protected properties.
        foreach ($reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED) as $reflectionProperty) {
            // Only include properties defined directly on the model, not inherited from Model base class.
            if ($reflectionProperty->getDeclaringClass()->getName() === $reflectionClass->getName()) {
                 $propAttrs = $this->attributeParser->getPropertyAttributes($reflectionProperty);
                 if (!empty($propAttrs)) { // Only add if there are attributes
                    $propertyAttributes[$reflectionProperty->getName()] = $propAttrs;
                 }
            }
        }

        $attributesArray = [
            'class' => $classAttributes,
            'properties' => $propertyAttributes,
        ];

        return new ModelSchema(
            class: $modelClass,
            table: $instance->getTable(),
            attributes: $attributesArray, // Pass the new structured array
            relationships: [], // Placeholder for now
            casts: $instance->getCasts(),
            fillable: $instance->getFillable(),
            hidden: $instance->getHidden()
        );
    }
}
