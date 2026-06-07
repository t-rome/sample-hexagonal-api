<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http;

use App\Shared\Application\Bus\CommandBusInterface;
use App\User\Application\Command\RegisterUser\RegisterUserCommand;
use App\User\Domain\Model\User;
use App\User\Domain\Port\TokenRevocationInterface;
use App\User\Infrastructure\Http\Dto\RegisterUserDto;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly TokenRevocationInterface $revocation,
    ) {
    }

    #[Route('/register', methods: ['POST'])]
    public function register(#[MapRequestPayload] RegisterUserDto $dto): JsonResponse
    {
        $user = $this->commandBus->dispatch(new RegisterUserCommand($dto->email, $dto->password));
        \assert($user instanceof User);

        return $this->json(['id' => $user->getId(), 'email' => $user->getEmail()], Response::HTTP_CREATED);
    }

    #[Route('/login', methods: ['POST'])]
    public function login(): never
    {
        throw new \LogicException('Handled by the json_login firewall.');
    }

    #[Route('/logout', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function logout(Request $request): JsonResponse
    {
        $token = $request->headers->get('Authorization', '');
        $token = str_replace('Bearer ', '', $token);

        $payload = $this->jwtManager->parse($token);

        if (isset($payload['jti'], $payload['exp'])) {
            $this->revocation->revoke(
                $payload['jti'],
                new \DateTimeImmutable()->setTimestamp($payload['exp']),
            );
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
