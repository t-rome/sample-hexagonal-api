<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/** @extends Voter<string, null> */
final class ProductVoter extends Voter
{
    public const string CREATE = 'product.create';
    public const string UPDATE = 'product.update';
    public const string DELETE = 'product.delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::CREATE, self::UPDATE, self::DELETE], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if (null === $token->getUser()) {
            return false;
        }

        return in_array('ROLE_ADMIN', $token->getRoleNames(), true);
    }
}
