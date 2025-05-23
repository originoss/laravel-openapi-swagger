<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ config('openapi.ui.title', 'OpenAPI UI') }}</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
    <link rel="icon" type="image/png" href="https://unpkg.com/swagger-ui-dist@5/favicon-32x32.png" sizes="32x32" />
    <link rel="icon" type="image/png" href="https://unpkg.com/swagger-ui-dist@5/favicon-16x16.png" sizes="16x16" />
    <style>
        html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin:0; background: #fafafa; }
        
        /* Format selector styles */
        .format-selector {
            position: absolute;
            top: 10px;
            right: 20px;
            z-index: 1000;
            display: flex;
            align-items: center;
            background: #fff;
            padding: 5px 10px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .format-selector label {
            margin-right: 10px;
            font-weight: bold;
            font-size: 14px;
            font-family: sans-serif;
        }
        .format-selector select {
            padding: 5px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>
    <!-- Format selector dropdown -->
    <div class="format-selector">
        <label for="spec-format">Format:</label>
        <select id="spec-format" onchange="changeSpecFormat(this.value)">
            <option value="json" selected>JSON</option>
            <option value="yaml">YAML</option>
        </select>
    </div>
    
    <div id="swagger-ui"></div>
    
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js" charset="UTF-8"> </script>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js" charset="UTF-8"> </script>
    <script>
    // Store URLs for different formats
    const specUrls = {
        json: "{{ route(config('openapi.ui.spec_route_name_json', 'openapi.json')) }}",
        yaml: "{{ route(config('openapi.paths.yaml_route_name', 'openapi.yaml')) }}"
    };
    
    // Initialize Swagger UI with the default format (JSON)
    let ui;
    
    window.onload = function() {
        initSwaggerUI(specUrls.json);
        
        // Check if format is specified in URL hash and set it
        const hash = window.location.hash;
        if (hash.includes('format=yaml')) {
            document.getElementById('spec-format').value = 'yaml';
            changeSpecFormat('yaml');
        }
    };
    
    function initSwaggerUI(url) {
        ui = SwaggerUIBundle({
            url: url,
            dom_id: '#swagger-ui',
            deepLinking: true,
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset
            ],
            plugins: [
                SwaggerUIBundle.plugins.DownloadUrl
            ],
            layout: "StandaloneLayout"
        });
        window.ui = ui;
    }
    
    function changeSpecFormat(format) {
        // Update URL hash to persist format selection
        window.location.hash = `format=${format}`;
        
        // Update Swagger UI with the selected format
        ui.specActions.updateUrl(specUrls[format]);
        ui.specActions.download();
    }
    </script>
</body>
</html>
