<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(name: 'app:db:reset', description: 'Drop, create, migrate and load fixtures — dev/test only')]
class ResetDatabaseCommand extends Command
{
    public function __construct(private readonly KernelInterface $kernel)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!in_array($this->kernel->getEnvironment(), ['dev', 'test'])) {
            $io->error('This command can only be run in dev/test environments.');

            return Command::FAILURE;
        }

        $app = $this->getApplication() ?? throw new \LogicException('No application context.');

        $steps = [
            'doctrine:database:drop' => ['--force' => true, '--if-exists' => true],
            'doctrine:database:create' => [],
            'doctrine:migrations:migrate' => ['--no-interaction' => true],
            'doctrine:fixtures:load' => ['--no-interaction' => true],
        ];

        foreach ($steps as $name => $args) {
            $io->section($name);
            $code = $app->find($name)->run(new ArrayInput($args), $output);
            if (Command::SUCCESS !== $code) {
                $io->error("Step '$name' failed (exit $code).");

                return Command::FAILURE;
            }
        }

        $io->success('Database reset complete.');

        return Command::SUCCESS;
    }
}
