<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(name: 'app:openapi:build', description: 'Merge OpenAPI spec fragments into docs/openapi.yaml')]
final class BuildOpenApiCommand extends Command
{
    public function __construct(private readonly string $projectDir)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $specsDir = $this->projectDir.'/docs/openapi';
        $outputFile = $this->projectDir.'/docs/openapi.yaml';

        $spec = Yaml::parseFile($specsDir.'/base.yaml');
        $spec['paths'] = [];
        $spec['components']['schemas'] = [];

        foreach (glob($specsDir.'/schemas/*.yaml') ?: [] as $file) {
            $schemas = Yaml::parseFile($file);
            $spec['components']['schemas'] = array_merge($spec['components']['schemas'], $schemas);
        }

        foreach (glob($specsDir.'/paths/**/*.yaml') ?: [] as $file) {
            $paths = Yaml::parseFile($file);
            foreach ($paths as $path => $operations) {
                if (!isset($spec['paths'][$path])) {
                    $spec['paths'][$path] = [];
                }
                $spec['paths'][$path] = array_merge($spec['paths'][$path], $operations);
            }
        }

        $yaml = Yaml::dump($spec, 10, 2);

        // Symfony Yaml serializes empty arrays as {} — OpenAPI requires []
        $yaml = preg_replace('/:\s*\{\s*\}/', ': []', $yaml);

        file_put_contents($outputFile, $yaml);

        $io->success('OpenAPI spec written to docs/openapi.yaml');

        return Command::SUCCESS;
    }
}
