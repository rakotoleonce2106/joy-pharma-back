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
            'datatable' => $datatable->createView(),
            'section' => 'all',
        ]);
    }

    #[Route('/user/delivers', name: 'admin_user_delivers')]
    public function delivers(Request $request): Response
    {
        // Get all users and filter in PHP
        $allUsers = $this->userRepository->findByRole('ROLE_DELIVER');
        
        // Create query from array - we'll use a custom approach
        $query = $this->userRepository->createQueryBuilder('u')
            ->where('u.id IN (:ids)')
            ->setParameter('ids', array_map(fn(User $u) => $u->getId(), $allUsers));

        $datatable = $this->createNamedDataTable('users_delivers', \App\DataTable\Type\DeliverDataTableType::class, $query);
        $datatable->handleRequest($request);

        return $this->render('admin/user/index.html.twig', [
            'datatable' => $datatable->createView(),
            'section' => 'delivers',
        ]);
    }

    #[Route('/user/stores', name: 'admin_user_stores')]
    public function stores(Request $request): Response
    {
        // Get all users and filter in PHP
        $allUsers = $this->userRepository->findByRole('ROLE_STORE');
        
        // Create query from array
        $query = $this->userRepository->createQueryBuilder('u')
            ->where('u.id IN (:ids)')
            ->setParameter('ids', array_map(fn(User $u) => $u->getId(), $allUsers));

        $datatable = $this->createNamedDataTable('users_stores', UserDataTableType::class, $query);
        $datatable->handleRequest($request);

        return $this->render('admin/user/index.html.twig', [
            'datatable' => $datatable->createView(),
            'section' => 'stores',
        ]);
    }

    #[Route('/user/customers', name: 'admin_user_customers')]
    public function customers(Request $request): Response
    {
        // Get all customers and filter in PHP
        $allUsers = $this->userRepository->findCustomersForDataTable();
        
        // Create query from array
        $query = $this->userRepository->createQueryBuilder('u')
            ->where('u.id IN (:ids)')
            ->setParameter('ids', array_map(fn(User $u) => $u->getId(), $allUsers));

        $datatable = $this->createNamedDataTable('users_customers', UserDataTableType::class, $query);
        $datatable->handleRequest($request);

        return $this->render('admin/user/index.html.twig', [
            'datatable' => $datatable->createView(),
            'section' => 'customers',
        ]);
    }

    #[Route('/user/{id}/edit', name: 'admin_user_edit')]
    public function editAction(Request $request, User $user): Response
    {

        $form = $this->createForm(UserType::class, $user, [
            'action' => $this->generateUrl('admin_user_edit', ['id' => $user->getId()])
        ]);
        return $this->handleUserForm($request, $form, $user, 'edit');
    }

    #[Route('/user/{id}/toggle-active', name: 'admin_user_toggle_active', methods: ['POST'])]
    public function toggleActive(User $user): Response
    {
        $user->setActive(!$user->getActive());
        $this->userService->updateUser($user);
        $this->addSuccessToast('Status updated', 'The user activation status has been updated.');
        return $this->redirectToRoute('admin_user');
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
            // Ensure the active field is properly set (handle unchecked checkboxes)
            if (!$form->get('active')->getData()) {
                $user->setActive(false);
            }

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
