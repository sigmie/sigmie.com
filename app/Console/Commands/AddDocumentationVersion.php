<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AddDocumentationVersion extends Command
{
    protected $signature = 'docs:add-version {version} {--status=stable} {--label=} {--default=false}';
    
    protected $description = 'Add a new documentation version';

    public function handle()
    {
        $version = $this->argument('version');
        $status = $this->option('status');
        $label = $this->option('label') ?: $version . '.x';
        $default = $this->option('default');

        // Create docs directory
        $docsPath = base_path("docs/{$version}");
        if (!File::exists($docsPath)) {
            File::makeDirectory($docsPath, 0755, true);
            $this->info("Created documentation directory: docs/{$version}");
        }

        // Create a basic introduction file if it doesn't exist
        $introPath = $docsPath . '/introduction.md';
        if (!File::exists($introPath)) {
            $introContent = "# Introduction\n\nWelcome to {$label} documentation.\n";
            File::put($introPath, $introContent);
            $this->info("Created introduction file: docs/{$version}/introduction.md");
        }

        // Update the docs config
        $configPath = config_path('docs.php');
        $config = include $configPath;

        // Add to versions array
        $newVersion = [
            'value' => $version,
            'label' => $label,
            'status' => $status,
        ];

        if ($default) {
            // Remove default from existing versions
            foreach ($config['versions'] as &$v) {
                unset($v['default']);
            }
            $newVersion['default'] = true;
        }

        // Check if version already exists
        $exists = false;
        foreach ($config['versions'] as &$v) {
            if ($v['value'] === $version) {
                $v = $newVersion;
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            $config['versions'][] = $newVersion;
        }

        // Add basic navigation structure for new version
        if (!isset($config[$version])) {
            $config[$version] = [
                'navigation' => [
                    [
                        'title' => 'Getting Started',
                        'links' => [
                            [
                                'title' => 'Introduction',
                                'href' => "/docs/{$version}/introduction"
                            ]
                        ]
                    ]
                ]
            ];
        }

        // Write the updated config back to file
        $configContent = "<?php\n\nreturn " . $this->varExport($config, true) . ";\n";
        File::put($configPath, $configContent);

        $this->info("Documentation version '{$version}' has been " . ($exists ? 'updated' : 'added'));
        
        if ($default) {
            $this->info("Set as default version");
        }

        $this->info("You can now add documentation files to: docs/{$version}/");
    }

    private function varExport($var, $indent = false)
    {
        switch (gettype($var)) {
            case 'string':
                return "'" . addcslashes($var, '\\\'') . "'";
            case 'array':
                $indexed = array_keys($var) === range(0, count($var) - 1);
                $r = [];
                foreach ($var as $key => $value) {
                    $r[] = ($indent ? "\n        " : '')
                        . ($indexed ? '' : $this->varExport($key) . ' => ')
                        . $this->varExport($value, true);
                }
                return '[' . implode(', ' . ($indent ? '' : ''), $r) . ($indent ? "\n    " : '') . ']';
            case 'boolean':
                return $var ? 'true' : 'false';
            default:
                return var_export($var, true);
        }
    }
}