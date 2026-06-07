<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http\ExceptionMapper;

use App\Shared\Infrastructure\Http\ExceptionMapper\HttpExceptionMapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class HttpExceptionMapperTest extends TestCase
{
    private HttpExceptionMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new HttpExceptionMapper();
    }

    public function testSupportsAccessDeniedException(): void
    {
        $this->assertTrue($this->mapper->supports(new AccessDeniedException()));
    }

    public function testSupportsHttpExceptionWithValidationFailedAsPrevious(): void
    {
        $validationException = new ValidationFailedException('', new ConstraintViolationList());
        $httpException = new UnprocessableEntityHttpException(previous: $validationException);

        $this->assertTrue($this->mapper->supports($httpException));
    }

    public function testDoesNotSupportHttpExceptionWithoutValidationPrevious(): void
    {
        $this->assertFalse($this->mapper->supports(new UnprocessableEntityHttpException()));
    }

    public function testDoesNotSupportUnrelatedExceptions(): void
    {
        $this->assertFalse($this->mapper->supports(new \RuntimeException('unrelated')));
    }

    public function testAccessDeniedReturns403(): void
    {
        $response = $this->mapper->toResponse(new AccessDeniedException());

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertStringContainsString('Access denied', $response->getContent() ?: '');
    }

    public function testValidationFailureReturns422WithViolations(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('Must not be blank', null, [], '', 'email', ''),
            new ConstraintViolation('Must be at least 8 characters', null, [], '', 'password', 'abc'),
        ]);
        $validationException = new ValidationFailedException('', $violations);
        $httpException = new UnprocessableEntityHttpException(previous: $validationException);

        $response = $this->mapper->toResponse($httpException);
        $body = json_decode($response->getContent() ?: '{}', true);

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertSame('Validation failed', $body['error']);
        $this->assertCount(2, $body['violations']);
        $this->assertSame('email', $body['violations'][0]['field']);
        $this->assertSame('Must not be blank', $body['violations'][0]['message']);
    }
}
