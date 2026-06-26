<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger\Middleware;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

/**
 * Re-throws the original domain exception when a Messenger handler fails.
 *
 * By default Symfony Messenger wraps handler exceptions in HandlerFailedException.
 * That would hide domain exceptions (e.g. InsufficientStockException) from the
 * HTTP exception-mapper pipeline, causing unhandled 500 responses instead of the
 * expected 4xx.
 *
 * This middleware sits in front of the handler middleware and transparently
 * re-throws the cause, so the rest of the kernel — including ApiExceptionSubscriber
 * — receives the original domain exception unchanged.
 */
final class UnwrapDomainExceptionMiddleware implements MiddlewareInterface
{
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        try {
            return $stack->next()->handle($envelope, $stack);
        } catch (HandlerFailedException $e) {
            throw $e->getPrevious() ?? $e;
        }
    }
}
