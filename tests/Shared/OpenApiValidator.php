<?php

declare(strict_types=1);

namespace App\Tests\Shared;

use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\Helper;
use Opis\JsonSchema\Validator;
use Symfony\Component\Yaml\Yaml;

final class OpenApiValidator
{
    private array $spec;
    private Validator $validator;

    public function __construct(string $specPath)
    {
        $this->spec = Yaml::parseFile($specPath);
        $this->validator = new Validator();
    }

    public function resolveSpecPath(string $path): string
    {
        if (isset($this->spec['paths'][$path])) {
            return $path;
        }

        foreach (array_keys($this->spec['paths']) as $specPath) {
            $parts = preg_split('~\{[^}]+\}~', $specPath);
            $pattern = '~^'.implode('[^/]+', array_map(fn ($p) => preg_quote($p, '~'), $parts)).'$~';
            if (preg_match($pattern, $path)) {
                return $specPath;
            }
        }

        throw new \InvalidArgumentException("No spec path matches: {$path}");
    }

    public function hasResponseSchema(string $specPath, string $method, int $statusCode): bool
    {
        $operation = $this->spec['paths'][$specPath][strtolower($method)] ?? null;
        $response = $operation['responses'][(string) $statusCode] ?? null;

        return isset($response['content']['application/json']['schema']);
    }

    public function assertResponse(
        string $path,
        string $method,
        int $statusCode,
        mixed $body,
    ): void {
        $schema = $this->resolveResponseSchema($path, $method, $statusCode);

        $result = $this->validator->validate(
            Helper::toJSON($body),
            json_encode($schema, \JSON_THROW_ON_ERROR),
        );

        if (!$result->isValid()) {
            throw new \PHPUnit\Framework\AssertionFailedError(\sprintf("Response for %s %s [%d] does not match OpenAPI spec:\n%s", strtoupper($method), $path, $statusCode, $this->formatErrors($result->error())));
        }
    }

    private function resolveResponseSchema(string $path, string $method, int $statusCode): array
    {
        $operation = $this->spec['paths'][$path][strtolower($method)]
            ?? throw new \InvalidArgumentException("No operation defined for {$method} {$path}");

        $response = $operation['responses'][(string) $statusCode]
            ?? throw new \InvalidArgumentException("No response defined for status {$statusCode} on {$method} {$path}");

        $schema = $response['content']['application/json']['schema']
            ?? throw new \InvalidArgumentException("No application/json schema for {$statusCode} on {$method} {$path}");

        return $this->resolveRefs($schema);
    }

    private function resolveRefs(array $schema): array
    {
        if (isset($schema['$ref'])) {
            $schema = $this->resolveRef($schema['$ref']);
        }

        foreach ($schema as $key => $value) {
            if (\is_array($value)) {
                $schema[$key] = $this->resolveRefs($value);
            }
        }

        return $this->normalizeNullable($schema);
    }

    /** Convert OpenAPI nullable:true into JSON Schema type arrays. */
    private function normalizeNullable(array $schema): array
    {
        if (isset($schema['nullable']) && true === $schema['nullable']) {
            unset($schema['nullable']);
            if (isset($schema['type']) && \is_string($schema['type'])) {
                $schema['type'] = [$schema['type'], 'null'];
            }
        }

        return $schema;
    }

    private function resolveRef(string $ref): array
    {
        // Only handles #/components/schemas/Name
        preg_match('~#/components/schemas/(.+)~', $ref, $matches);
        $name = $matches[1] ?? throw new \InvalidArgumentException("Cannot resolve \$ref: {$ref}");

        return $this->spec['components']['schemas'][$name]
            ?? throw new \InvalidArgumentException("Schema not found: {$name}");
    }

    private function formatErrors(?ValidationError $error, int $depth = 0): string
    {
        if (null === $error) {
            return '';
        }

        $indent = str_repeat('  ', $depth);
        $message = $indent.$this->interpolate($error->message(), $error->args());

        foreach ($error->subErrors() as $sub) {
            $message .= "\n".$this->formatErrors($sub, $depth + 1);
        }

        return $message;
    }

    private function interpolate(string $message, array $args): string
    {
        foreach ($args as $key => $value) {
            $message = str_replace('{'.$key.'}', \is_scalar($value) ? (string) $value : json_encode($value), $message);
        }

        return $message;
    }
}
