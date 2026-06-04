<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console;

use Symfony\Component\Yaml\Yaml;

final readonly class OpenApiBuilder
{
    public function __construct(private string $projectDir)
    {
    }

    public function build(): string
    {
        $contractDir = $this->projectDir.'/api-contract';

        $spec = Yaml::parseFile($contractDir.'/base.yaml');
        $spec['paths'] = [];
        $spec['components']['schemas'] = [];

        foreach (glob($contractDir.'/schemas/*.yaml') ?: [] as $file) {
            $spec['components']['schemas'] = array_merge(
                $spec['components']['schemas'],
                Yaml::parseFile($file),
            );
        }

        foreach (glob($contractDir.'/paths/**/*.yaml') ?: [] as $file) {
            $paths = Yaml::parseFile($file);
            foreach ($paths as $path => $operations) {
                $spec['paths'][$path] = array_merge($spec['paths'][$path] ?? [], $operations);
            }
        }

        $yaml = Yaml::dump($spec, 10, 2);

        return preg_replace('/:\s*\{\s*\}/', ': []', $yaml) ?? $yaml;
    }
}
