<?php

namespace App\Controller\Api;

use App\Dto\SocialInput;
use App\Service\UserService;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

class FacebookAuthController extends AbstractController
{
    public function __construct(private readonly UserService $userService,private readonly AuthenticationSuccessHandler $successHandler)
    {
    }

    public function __invoke(
        #[MapRequestPayload] SocialInput $input,
        UserService $userService
    ): Response {
        $userTemp= $this->userService->getUserByEmail($input->email);
        if($userTemp){
            return $this->successHandler->handleAuthenticationSuccess($userTemp);
        }
        // Create a new user entity
        $user = $this->userService->createUserByFacebook($input);
        $userWithPassword = $this->userService->hashPassword($user);
        $this->userService->saveUser($userWithPassword);
        return  $this->successHandler->handleAuthenticationSuccess($user);
    }
}
