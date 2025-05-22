# Laravel OpenAPI Annotation Library - Demo Application

This directory contains a minimal Laravel application demonstrating the usage of the `origin-oss/laravel-openapi` package (the parent library).

## Purpose

The purpose of this demo is to provide a practical, runnable example of how to:

1.  Install and configure the `laravel-openapi` package.
2.  Annotate Eloquent models with `#[Schema]` and `#[Property]` attributes.
3.  Annotate controllers and their methods with `#[Operation]`, `#[Parameter]`, `#[RequestBody]`, and `#[Response]` attributes.
4.  Generate an OpenAPI specification document using the Artisan command.
5.  View the generated documentation via the built-in Swagger UI.

## Included Demo

This demo implements a very simple **Tasks API** with basic CRUD operations for tasks.

-   **Model**: `app/Models/Task.php` (annotated)
-   **Controller**: `app/Http/Controllers/TaskController.php` (annotated)
-   **API Routes**: `routes/api.php`
-   **OpenAPI Config**: `config/openapi.php` (copied from the parent package)

## Theoretical Usage

While this demo is part of the main library's repository and not a standalone Composer package, here's how you would typically interact with such a demo if it were set up as a runnable Laravel project:

1.  **Clone the Repository (if standalone) & Navigate to Demo Directory**:
    ```bash
    # git clone <repository-url>
    cd path/to/laravel-openapi/demo 
    ```

2.  **Install Dependencies**:
    This demo's `composer.json` is configured to use the parent `laravel-openapi` library from its local path using a "path" repository.
    ```bash
    composer install
    ```

3.  **Set up Environment**:
    Copy `.env.example` to `.env` and set your application key (though not strictly needed for generation if not using DB features not part of this demo's focus).
    ```bash
    cp .env.example .env
    php artisan key:generate 
    ```
    (Note: The demo primarily uses in-memory or placeholder data where possible, focusing on annotation processing.)

4.  **Generate the OpenAPI Specification**:
    Run the Artisan command provided by the `laravel-openapi` package:
    ```bash
    php artisan openapi:generate
    ```
    This will generate (by default) `public/openapi.json` and/or `public/openapi.yaml`.

5.  **View the Documentation**:
    If your Laravel development server is running (e.g., `php artisan serve`), you can access the Swagger UI:
    -   Open your browser to `http://localhost:8000/openapi/ui` (or the configured UI path).

This will display the interactive API documentation generated from the annotations in this demo application. You can also access the raw spec files at `/openapi.json` and `/openapi.yaml`.
