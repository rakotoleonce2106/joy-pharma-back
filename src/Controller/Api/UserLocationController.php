<?php

namespace App\Controller\Api;

use App\Entity\Location;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserLocationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TokenStorageInterface $tokenStorage
    ) {
    }

    public function __invoke(array $uriVariables = []): JsonResponse
    {
        // Get the current authenticated user
        $token = $this->tokenStorage->getToken();
        if (!$token || !$token->getUser() instanceof User) {
            throw new BadRequestHttpException('User not authenticated');
        }

        /** @var User $user */
        $user = $token->getUser();

        // Get locationId from URI variables
        $locationId = $uriVariables['locationId'] ?? null;
        if (!$locationId) {
            throw new BadRequestHttpException('Location ID is required');
        }

        // Find the location
        $location = $this->entityManager->getRepository(Location::class)->find($locationId);
        if (!$location) {
            throw new NotFoundHttpException('Location not found');
        }

        // Check if the location belongs to the user
        if (!$user->getLocations()->contains($location)) {
            throw new BadRequestHttpException('This location does not belong to the current user');
        }

        // Remove the location from user's locations
        $user->removeLocation($location);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Location removed successfully',
            'locationId' => $locationId
        ], Response::HTTP_OK);
    }
}

