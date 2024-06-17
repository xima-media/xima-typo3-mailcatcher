<?php

namespace Xima\XimaTypo3Mailcatcher\Tests\Acceptance\Support\Helper;

use Codeception\Module;
use Composer\InstalledVersions;
use ReflectionClass;

class ExtensionConfiguration
{
    /**
     * @var non-empty-string
     */
    private string $scriptPath;

    private Module\Asserts $asserts;

    private Module\Cli $cli;

    public function __construct(
        Module\Asserts $asserts,
        Module\Cli $cli
    ) {
        $this->scriptPath = $this->determineScriptPath();
        $this->asserts = $asserts;
        $this->cli = $cli;
    }

    /**
     * @return non-empty-string
     */
    private function determineScriptPath(): string
    {
        $buildDir = \dirname(self::getVendorDirectory());

        return file_exists($buildDir . '/vendor/bin/typo3cms') ? $buildDir . '/vendor/bin/typo3cms' : $buildDir . '/bin/typo3';
    }

    private static function getVendorDirectory(): string
    {
        $reflectionClass = new ReflectionClass(InstalledVersions::class);
        $filename = $reflectionClass->getFileName();

        if (false === $filename) {
            throw new \RuntimeException('Vendor directory cannot be determined', 1709887555);
        }

        return dirname($filename, 2);
    }

    public function read(string $path): mixed
    {
        $command = $this->buildCommand(['configuration:showactive', 'MAIL/' . $path, '--json']);

        $this->cli->runShellCommand($command);

        $output = $this->cli->grabShellOutput();

        $this->asserts->assertJson($output);

        return json_decode($output, true);
    }

    /**
     * @param non-empty-list<scalar> $command
     * @return non-empty-string
     */
    private function buildCommand(array $command): string
    {
        $fullCommand = [$this->scriptPath, ...$command];
        $fullCommand = array_map('strval', $fullCommand);

        return implode(' ', array_map('escapeshellarg', $fullCommand));
    }

    /**
     * @param non-empty-string $path
     */
    public function write(string $path, mixed $value): void
    {
        $command = $this->buildCommand(['configuration:set', 'MAIL/' . $path, $value]);

        $this->cli->runShellCommand($command);
    }

    public function flushCache(): void
    {
        $command = $this->buildCommand(['cache:flush']);
        $this->cli->runShellCommand($command);
    }
}
