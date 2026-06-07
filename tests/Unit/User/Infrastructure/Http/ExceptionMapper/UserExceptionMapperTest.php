<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Http\ExceptionMapper;

use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Infrastructure\Http\ExceptionMapper\UserExceptionMapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class UserExceptionMapperTest extends TestCase
{
    private UserExceptionMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new UserExceptionMapper();
    }

    public function testSupportsUserAlreadyExistsException(): void
    {
        $this->assertTrue($this->mapper->supports(new UserAlreadyExistsException('user@example.com')));
    }

    public function testDoesNotSupportUnrelatedExceptions(): void
    {
        $this->assertFalse($this->mapper->supports(new \RuntimeException('unrelated')));
    }

    public function testUserAlreadyExistsReturns409(): void
    {
        $response = $this->mapper->toResponse(new UserAlreadyExistsException('user@example.com'));

        $this->assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertStringContainsString('user@example.com', $response->getContent() ?: '');
    }
}
