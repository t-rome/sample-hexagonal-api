<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Infrastructure\Security;

use App\Product\Infrastructure\Security\ProductVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class ProductVoterTest extends TestCase
{
    private ProductVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new ProductVoter();
    }

    public function testAdminIsGrantedAllAttributes(): void
    {
        $token = $this->token(['ROLE_ADMIN']);

        foreach ([ProductVoter::CREATE, ProductVoter::UPDATE, ProductVoter::DELETE] as $attribute) {
            self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, null, [$attribute]));
        }
    }

    public function testRegularUserIsDenied(): void
    {
        $token = $this->token(['ROLE_USER']);

        foreach ([ProductVoter::CREATE, ProductVoter::UPDATE, ProductVoter::DELETE] as $attribute) {
            self::assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, null, [$attribute]));
        }
    }

    public function testUnauthenticatedIsDenied(): void
    {
        $token = $this->token([], authenticated: false);

        self::assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, null, [ProductVoter::CREATE]));
    }

    public function testAbstainsForUnknownAttribute(): void
    {
        $token = $this->token(['ROLE_ADMIN']);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, null, ['unknown']));
    }

    private function token(array $roles, bool $authenticated = true): TokenInterface
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($authenticated ? $this->createStub(UserInterface::class) : null);
        $token->method('getRoleNames')->willReturn($roles);

        return $token;
    }
}
