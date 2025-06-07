<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DataTable\Type\UserDataTableType;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\UserService;
use App\Traits\ToastTrait;
use Kreyu\Bundle\DataTableBundle\DataTableFactoryAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    use DataTableFactoryAwareTrait;
    use ToastTrait;

    public  function __construct(
        private  readonly UserRepository $userRepository,
        private readonly UserService $userService,
    ) {}
    #[Route('/user', name: 'admin_user')]
    public function index(Request $request): Response
    {
        $query = $this->userRepository->createQueryBuilder('user');

        $datatable = $this->createNamedDataTable('users', UserDataTableType::class, $query);
        $datatable->handleRequest($request);

        return $this->render('admin/user/index.html.twig', [
            'datatable' => $datatable->createView()
        ]);
    }

    #[Route('/user/{id}/edit', name: 'admin_user_edit', defaults: ['title' => 'Edit user'])]
    public function editAction(Request $request, User $user): Response
    {

        $form = $this->createForm(UserType::class, $user, [
            'action' => $this->generateUrl('admin_user_edit', ['id' => $user->getId()])
        ]);
        return $this->handleUserForm($request, $form, $user, 'edit');
    }

        #[Route('/user/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public  function deleteAction(User $user): Response
    {
        $this->userService->deleteUser($user);
        $this->addSuccessToast('User deleted!', 'The user has been successfully deleted.');
        return $this->redirectToRoute('admin_user', status: Response::HTTP_SEE_OTHER);
    }



    private function handleUserForm(Request $request, $form, $user, string $action): Response
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            if ($action === 'create') {
                $this->userService->createUser($user);
            } else {
                $this->userService->updateUser($user);
            }


            $this->addSuccessToast(
                $action === 'create' ? 'User created!' : 'User updated!',
                "The user has been successfully {$action}d."
            );

            if ($request->headers->has('turbo-frame')) {
                $stream = $this->renderBlockView("admin/user/{$action}.html.twig", 'stream_success', [
                    'user' => $user
                ]);
                $this->addFlash('stream', $stream);
            }

            return $this->redirectToRoute('admin_user', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render("admin/user/{$action}.html.twig", [
            'user' => $user,
            'form' => $form
        ]);
    }
}
