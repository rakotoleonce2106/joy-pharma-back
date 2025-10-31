<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DataTable\Type\StoreDataTableType;
use App\DataTable\Type\StoreProductDataTableType;
use App\Entity\Store;
use App\Entity\StoreProduct;
use App\Entity\User;
use App\Form\StoreProductType;
use App\Form\StoreType;
use App\Repository\StoreProductRepository;
use App\Repository\StoreRepository;
use App\Service\StoreService;
use App\Service\MediaFileService;
use App\Service\UserService;
use App\Traits\ToastTrait;
use Doctrine\ORM\EntityManagerInterface;
use Kreyu\Bundle\DataTableBundle\DataTableFactoryAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class StoreController extends AbstractController
{
    use DataTableFactoryAwareTrait;
    use ToastTrait;

    public  function __construct(
        private  readonly StoreRepository $StoreRepository,
        private readonly StoreService $storeService,
        private readonly MediaFileService $mediaFileService,
        private readonly UserService $userService,
        private readonly EntityManagerInterface $entityManager,
        private readonly StoreProductRepository $storeProductRepository,
    ) {}
    #[Route('/store', name: 'admin_store')]
    public function index(Request $request): Response
    {
        $query = $this->StoreRepository->createQueryBuilder('store');

        $datatable = $this->createNamedDataTable('Stores', StoreDataTableType::class, $query);
        $datatable->handleRequest($request);

        return $this->render('admin/store/index.html.twig', [
            'datatable' => $datatable->createView()
        ]);
    }

    #[Route('/store/new', name: 'admin_store_new', defaults: ['title' => 'Create Store'])]
    public function createAction(Request $request): Response
    {
        $store = new Store();
        $form = $this->createForm(StoreType::class, $store, ['action' => $this->generateUrl('admin_store_new')]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $uploadedFile */
            $image = $form->get('image')->getData();
            if ($image) {
                $mediaFile = $this->mediaFileService->createMediaByFile($image, 'images/store/');
                $store->addImage($mediaFile);
            }

            // Get login credentials from form
            $ownerEmail = $form->get('ownerEmail')->getData();
            $ownerPassword = $form->get('ownerPassword')->getData();
            
            // Check if user already exists with this email
            $user = $this->userService->getUserByEmail($ownerEmail);
            if(!$user){
                $user = new User();
                $user->setEmail($ownerEmail);
                $user->setFirstName($store->getName());
                $user->setLastName('Store Owner');
                $user->setRoles(['ROLE_STORE']);
                
                // Use provided password or auto-generate
                $password = $ownerPassword ?: 'JoyPharma2025!';
                $user->setPassword($password);
                
                $userWithPassword = $this->userService->hashPassword($user);
                $this->userService->persistUser($userWithPassword);
            }
            
            // Set the owner relationship
            $store->setOwner($user);
           
            // Initialize StoreSetting with default business hours (if not already set)
            if (!$store->getSetting()) {
                $storeSetting = new \App\Entity\StoreSetting();
                $store->setSetting($storeSetting);
            }
           
            $this->storeService->createStore($store);
            $this->addSuccessToast('Store created!', "The Store has been successfully created. Login email: {$ownerEmail}");
            return $this->redirectToRoute('admin_store', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render("admin/store/create.html.twig", [
            'store' => $store,
            'form' => $form
        ]);
    }

    #[Route('/store/{id}/edit', name: 'admin_store_edit', defaults: ['title' => 'Edit Store'])]
    public function editAction(Request $request, int $id): Response
    {
        // Fetch store with eager loading of all relationships
        $store = $this->StoreRepository->createQueryBuilder('s')
            ->leftJoin('s.owner', 'o')
            ->leftJoin('s.contact', 'c')
            ->leftJoin('s.location', 'l')
            ->leftJoin('s.image', 'si')
            ->addSelect('o', 'c', 'l', 'si')
            ->where('s.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$store) {
            throw $this->createNotFoundException('Store not found');
        }

        $form = $this->createForm(StoreType::class, $store, [
            'action' => $this->generateUrl('admin_store_edit', ['id' => $store->getId()])
        ]);
        
        // Pre-populate email if owner exists
        if ($store->getOwner()) {
            $form->get('ownerEmail')->setData($store->getOwner()->getEmail());
        }

        // Create DataTable for store products with pagination and filters
        $storeProductsQuery = $this->storeProductRepository->createQueryBuilder('sp')
            ->leftJoin('sp.product', 'p')
            ->leftJoin('p.brand', 'b')
            ->leftJoin('p.images', 'img')
            ->addSelect('p', 'b', 'img')
            ->where('sp.store = :store')
            ->setParameter('store', $store);

        $productsDataTable = $this->createDataTable(StoreProductDataTableType::class, $storeProductsQuery, [
            'edit_route' => fn(array $params) => $this->generateUrl('admin_store_product_edit', $params),
            'delete_route' => fn(array $params) => $this->generateUrl('admin_store_product_delete', $params),
        ]);
        $productsDataTable->handleRequest($request);
        
        return $this->handleStoreForm($request, $form, $store, 'edit', $productsDataTable);
    }

    #[Route('/store/{id}/delete', name: 'admin_store_delete', methods: ['POST'])]
    public  function deleteAction(Store $store): Response
    {
        $this->storeService->deleteStore($store);
        $this->addSuccessToast('Store deleted!', 'The Store has been successfully deleted.');
        return $this->redirectToRoute('admin_store');
    }

    #[Route('/store/batch-delete', name: 'admin_store_batch_delete', methods: ['POST'])]
    public function batchDeleteAction(Request $request): Response
    {
        $StoreIds = $request->request->all('id');
        $this->storeService->batchDeleteStores(
            $StoreIds
        );

        $this->addSuccessToast("Stores deleted!", "The Stores have been successfully deleted.");
        return $this->redirectToRoute('admin_store');
    }


    private function handleStoreForm(Request $request, $form, Store $store, string $action, $productsDataTable = null): Response
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $uploadedFile */
            $image = $form->get('image')->getData();
            if ($image) {
                $mediaFile = $this->mediaFileService->createMediaByFile($image, 'images/store/');
                $store->addImage($mediaFile);
            }

            // Get login credentials from form
            $ownerEmail = $form->get('ownerEmail')->getData();
            $ownerPassword = $form->get('ownerPassword')->getData();
            
            // Get or create user
            $user = $store->getOwner();
            $isNewUser = false;
            
            if (!$user) {
                $user = $this->userService->getUserByEmail($ownerEmail);
                if (!$user) {
                    $user = new User();
                    $isNewUser = true;
                }
            }
            
            // Update user details
            $user->setEmail($ownerEmail);
            $user->setFirstName($store->getName());
            $user->setLastName('Store Owner');
            $user->setRoles(['ROLE_STORE']);
            
            // Update password if provided
            if ($ownerPassword) {
                $user->setPassword($ownerPassword);
                $user = $this->userService->hashPassword($user);
            }
            
            if ($isNewUser || $ownerPassword) {
                $this->userService->persistUser($user);
            }
            
            // Set the owner relationship
            $store->setOwner($user);

            $this->storeService->updateStore($store);

            $this->addSuccessToast('Store updated!', "The Store has been successfully updated.");

            if ($request->headers->has('turbo-frame')) {
                $stream = $this->renderBlockView("admin/store/{$action}.html.twig", 'stream_success', [
                    'store' => $store
                ]);
                $this->addFlash('stream', $stream);
            }

            return $this->redirectToRoute('admin_store', status: Response::HTTP_SEE_OTHER);
        }

        $templateData = [
            'store' => $store,
            'form' => $form
        ];

        if ($productsDataTable) {
            $templateData['productsDataTable'] = $productsDataTable->createView();
        }

        return $this->render("admin/store/{$action}.html.twig", $templateData);
    }

    #[Route('/store/{id}/product/add', name: 'admin_store_product_add', defaults: ['title' => 'Add Product to Store'])]
    public function addProductAction(Request $request, Store $store): Response
    {
        $storeProduct = new StoreProduct();
        $storeProduct->setStore($store);
        
        $form = $this->createForm(StoreProductType::class, $storeProduct);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($storeProduct);
            $this->entityManager->flush();

            $this->addSuccessToast('Product added!', 'The product has been successfully added to the store.');
            return $this->redirectToRoute('admin_store_edit', ['id' => $store->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render("admin/store/product-add.html.twig", [
            'store' => $store,
            'form' => $form
        ]);
    }

    #[Route('/store/{storeId}/product/{id}/edit', name: 'admin_store_product_edit', defaults: ['title' => 'Edit Store Product'])]
    public function editProductAction(Request $request, int $storeId, StoreProduct $storeProduct): Response
    {
        $store = $storeProduct->getStore();
        
        if ($store->getId() !== $storeId) {
            throw $this->createNotFoundException('Store product not found');
        }

        $form = $this->createForm(StoreProductType::class, $storeProduct);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addSuccessToast('Product updated!', 'The store product has been successfully updated.');
            return $this->redirectToRoute('admin_store_edit', ['id' => $storeId], Response::HTTP_SEE_OTHER);
        }

        return $this->render("admin/store/product-edit.html.twig", [
            'store' => $store,
            'storeProduct' => $storeProduct,
            'form' => $form
        ]);
    }

    #[Route('/store/{storeId}/product/{id}/delete', name: 'admin_store_product_delete', methods: ['POST'])]
    public function deleteProductAction(int $storeId, StoreProduct $storeProduct): Response
    {
        $store = $storeProduct->getStore();
        
        if ($store->getId() !== $storeId) {
            throw $this->createNotFoundException('Store product not found');
        }

        $this->entityManager->remove($storeProduct);
        $this->entityManager->flush();

        $this->addSuccessToast('Product removed!', 'The product has been successfully removed from the store.');
        return $this->redirectToRoute('admin_store_edit', ['id' => $storeId], Response::HTTP_SEE_OTHER);
    }
}
