<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

/**
 * Base class for all domain exceptions that map to API error responses.
 *
 * Subclasses must carry an #[ApiException] attribute declaring the application error code,
 * HTTP status code, and message template. The constructor reads this attribute via reflection,
 * interpolates {{ placeholder }} tokens from the context array, and populates the inherited
 * message, errorCode, and statusCode fields.
 *
 * This eliminates the need for exception mappers to hard-code HTTP status codes — they read
 * both errorCode() and statusCode() from the exception itself, so adding a new exception only
 * requires the attribute and a constructor; no mapper changes are needed.
 *
 * Subclasses should only override the constructor to accept typed parameters and forward a
 * context map to parent::__construct():
 *
 *   #[ApiException(errorCode: 1001, httpStatusCode: 404, message: 'Order "{{ id }}" not found.')]
 *   final class OrderNotFoundException extends ApiBaseException
 *   {
 *       public function __construct(int $id, ?\Throwable $previous = null)
 *       {
 *           parent::__construct(['id' => (string) $id], $previous);
 *       }
 *   }
 */
abstract class ApiBaseException extends \Exception
{
    protected int $errorCode;
    protected int $statusCode;

    public function __construct(
        /** @var array<string, string> */
        protected array $context = [],
        ?\Throwable $previous = null,
        ?int $overrideHttpStatusCode = null,
    ) {
        $reflection = new \ReflectionClass($this);
        $attributes = $reflection->getAttributes(ApiException::class);

        if (empty($attributes)) {
            throw new \LogicException('Missing #[ApiException] attribute on '.$reflection->getName());
        }

        $attribute = $attributes[0]->newInstance();

        $this->errorCode = $attribute->errorCode;
        $this->statusCode = $overrideHttpStatusCode ?? $attribute->httpStatusCode;

        $message = preg_replace_callback(
            '/{{\s*(\w+)\s*}}/',
            static fn ($matches) => $context[$matches[1]] ?? $matches[0],
            $attribute->message,
        ) ?? $attribute->message;

        parent::__construct($message, $this->errorCode, $previous);
    }

    public function errorCode(): int
    {
        return $this->errorCode;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
