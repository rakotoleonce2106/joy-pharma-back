<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DataTable\Type\StoreDataTableType;
use App\DataTable\Type\StoreProductDataTableType;
use App\Entity\Store;
use App\Entity\StoreProduct;
use App\Entity\User;
use App\Form\StoreProductType;
use App\Form\StoreSettingType;
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
        
        // Initialize location if null to prevent PropertyAccessor errors
        // The LocationType form will handle creating a Location object if needed
        if (!$store->getLocation()) {
            $store->setLocation(new \App\Entity\Location());
        }
        
        $form = $this->createForm(StoreType::class, $store, ['action' => $this->generateUrl('admin_store_new')]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // After form processing, check if location should be null (empty location or missing coordinates)
            $location = $store->getLocation();
            if ($location) {
                $latitude = $location->getLatitude();
                $longitude = $location->getLongitude();
                
                // If latitude or longitude is null, we cannot save the location (database constraint)
                // Set location to null even if address has a value - coordinates are required
                if (($latitude === null || $latitude === 0.0) || 
                    ($longitude === null || $longitude === 0.0)) {
                    $store->setLocation(null);
                } elseif (empty($location->getAddress()) && 
                    ($latitude === null || $latitude === 0.0) && 
                    ($longitude === null || $longitude === 0.0)) {
                    // All location fields are empty, set to null
                    $store->setLocation(null);
                }
            }
            /** @var UploadedFile|null $uploadedFile */
            $image = $form->get('image')->getData();
            if ($image) {
                $mediaFile = $this->mediaFileService->createMediaByFile($image, 'images/store/');
                $store->addImage($mediaFile);
            }

            // Get login credentials from form
            $ownerEmail = $form->get('ownerEmail')->getData();
            $ownerPasswordField = $form->get('ownerPassword');
            $ownerPasswordData = $ownerPasswordField->getData();
            
            // Extract password from RepeatedType (returns array with 'first' key)
            $ownerPassword = null;
            if ($ownerPasswordData && is_array($ownerPasswordData) && isset($ownerPasswordData['first'])) {
                $ownerPassword = $ownerPasswordData['first'];
            } elseif (is_string($ownerPasswordData) && !empty($ownerPasswordData)) {
                $ownerPassword = $ownerPasswordData;
            }
            
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
                
                // Hash and set password
                $user = $this->userService->hashPassword($user, $password);
                $this->userService->persistUser($user);
            }
            
            // Set the bidirectional owner relationship
            $store->setOwner($user);
            $user->setStore($store); // Important: Set bidirectional relationship
           
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
        // Fetch store with eager loading of all relationships including settings
        $store = $this->StoreRepository->createQueryBuilder('s')
            ->leftJoin('s.owner', 'o')
            ->leftJoin('s.contact', 'c')
            ->leftJoin('s.location', 'l')
            ->leftJoin('s.image', 'si')
            ->leftJoin('s.setting', 'st')
            ->leftJoin('st.mondayHours', 'mh')
            ->leftJoin('st.tuesdayHours', 'th')
            ->leftJoin('st.wednesdayHours', 'wh')
            ->leftJoin('st.thursdayHours', 'thh')
            ->leftJoin('st.fridayHours', 'fh')
            ->leftJoin('st.saturdayHours', 'sah')
            ->leftJoin('st.sundayHours', 'suh')
            ->addSelect('o', 'c', 'l', 'si', 'st', 'mh', 'th', 'wh', 'thh', 'fh', 'sah', 'suh')
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
        
        // Initialize StoreSetting if it doesn't exist
        if (!$store->getSetting()) {
            $storeSetting = new \App\Entity\StoreSetting();
            $store->setSetting($storeSetting);
            $this->entityManager->persist($storeSetting);
            $this->entityManager->flush();
        }
        
        // Ensure all BusinessHours are initialized (they can be null)
        $storeSetting = $store->getSetting();
        if ($storeSetting) {
            $needsFlush = false;
            // Check and initialize each BusinessHours if null
            $hoursMapping = [
                'mondayHours', 'tuesdayHours', 'wednesdayHours', 'thursdayHours',
                'fridayHours', 'saturdayHours', 'sundayHours'
            ];
            
            foreach ($hoursMapping as $property) {
                $getter = 'get' . ucfirst($property);
                $setter = 'set' . ucfirst($property);
                $hours = $storeSetting->$getter();
                
                if (!$hours) {
                    // Create default BusinessHours based on day
                    if ($property === 'sundayHours') {
                        $newHours = new \App\Entity\BusinessHours(null, null, true); // Closed
                    } else {
                        $newHours = new \App\Entity\BusinessHours('09:00', '18:00', false);
                    }
                    $this->entityManager->persist($newHours);
                    $storeSetting->$setter($newHours);
                    $needsFlush = true;
                }
            }
            
            if ($needsFlush) {
                $this->entityManager->persist($storeSetting);
                $this->entityManager->flush();
                // Refresh the storeSetting to ensure all relationships are loaded
                $this->entityManager->refresh($storeSetting);
                // Also refresh the store to ensure it has the updated setting
                $this->entityManager->refresh($store);
            }
        }
        
        // Ensure storeSetting exists and all BusinessHours are initialized
        $storeSetting = $store->getSetting();
        if (!$storeSetting) {
            $storeSetting = new \App\Entity\StoreSetting();
            $store->setSetting($storeSetting);
            $storeSetting->initializeDefaults();
            // Persist all newly created BusinessHours
            foreach (['mondayHours', 'tuesdayHours', 'wednesdayHours', 'thursdayHours', 'fridayHours', 'saturdayHours', 'sundayHours'] as $prop) {
                $getter = 'get' . ucfirst($prop);
                $hours = $storeSetting->$getter();
                if ($hours) {
                    $this->entityManager->persist($hours);
                }
            }
            $this->entityManager->persist($storeSetting);
            $this->entityManager->flush();
            // Refresh to ensure all relationships are loaded
            $this->entityManager->refresh($storeSetting);
        } else {
            // Double-check all BusinessHours are initialized (safety check)
            $hoursMapping = [
                'mondayHours', 'tuesdayHours', 'wednesdayHours', 'thursdayHours',
                'fridayHours', 'saturdayHours', 'sundayHours'
            ];
            
            $needsFlush = false;
            foreach ($hoursMapping as $property) {
                $getter = 'get' . ucfirst($property);
                $setter = 'set' . ucfirst($property);
                $hours = $storeSetting->$getter();
                
                if (!$hours) {
                    // Create default BusinessHours based on day
                    if ($property === 'sundayHours') {
                        $newHours = new \App\Entity\BusinessHours(null, null, true); // Closed
                    } else {
                        $newHours = new \App\Entity\BusinessHours('09:00', '18:00', false);
                    }
                    $this->entityManager->persist($newHours);
                    $storeSetting->$setter($newHours);
                    $needsFlush = true;
                }
            }
            
            if ($needsFlush) {
                $this->entityManager->persist($storeSetting);
                $this->entityManager->flush();
                $this->entityManager->refresh($storeSetting);
            }
        }
        
        // Final verification and initialization: ensure all BusinessHours exist before creating form
        // Call initializeDefaults to ensure all BusinessHours are set
        $storeSetting->initializeDefaults();
        
        // Double-check all BusinessHours are not null
        foreach (['mondayHours', 'tuesdayHours', 'wednesdayHours', 'thursdayHours', 'fridayHours', 'saturdayHours', 'sundayHours'] as $prop) {
            $getter = 'get' . ucfirst($prop);
            $hours = $storeSetting->$getter();
            if (!$hours) {
                throw new \RuntimeException("BusinessHours for {$prop} is still null after initialization");
            }
        }
        
        $storeSettingForm = $this->createForm(StoreSettingType::class, $storeSetting, [
            'action' => $this->generateUrl('admin_store_setting_update', ['id' => $store->getId()])
        ]);
        
        return $this->handleStoreForm($request, $form, $store, 'edit', $productsDataTable, $storeSettingForm);
    }

    #[Route('/store/{id}/delete', name: 'admin_store_delete', methods: ['POST'])]
    public function deleteAction(Request $request, Store $store): Response
    {
        try {
            $this->storeService->deleteStore($store);
            $this->addSuccessToast('Store deleted!', 'The Store has been successfully deleted.');
        } catch (\Exception $e) {
            $this->addErrorToast('Delete failed!', 'Unable to delete store: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('admin_store', status: Response::HTTP_SEE_OTHER);
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


    private function handleStoreForm(Request $request, $form, Store $store, string $action, $productsDataTable = null, $storeSettingForm = null): Response
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
            $ownerPasswordField = $form->get('ownerPassword');
            $ownerPasswordData = $ownerPasswordField->getData();
            
            // Extract password from RepeatedType (returns array with 'first' key)
            $ownerPassword = null;
            if ($ownerPasswordData && is_array($ownerPasswordData) && isset($ownerPasswordData['first'])) {
                $ownerPassword = $ownerPasswordData['first'];
            } elseif (is_string($ownerPasswordData) && !empty($ownerPasswordData)) {
                $ownerPassword = $ownerPasswordData;
            }
            
            // Get or create user
            $user = $store->getOwner();
            $isNewUser = false;
            $passwordUpdated = false;
            
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
            if ($ownerPassword && !empty(trim($ownerPassword))) {
                // Hash and set password
                $user = $this->userService->hashPassword($user, $ownerPassword);
                $passwordUpdated = true;
            }
            
            // Persist user if it's new or password was updated
            if ($isNewUser || $passwordUpdated) {
                $this->userService->persistUser($user);
            } else {
                // Still need to persist user in case email or other details changed
                $this->entityManager->persist($user);
            }
            
            // Set the bidirectional owner relationship
            $store->setOwner($user);
            $user->setStore($store); // Important: Set bidirectional relationship

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

        if ($storeSettingForm) {
            $templateData['storeSettingForm'] = $storeSettingForm->createView();
        }

        return $this->render("admin/store/{$action}.html.twig", $templateData);
    }

    #[Route('/store/{id}/setting/update', name: 'admin_store_setting_update', defaults: ['title' => 'Update Store Settings'], methods: ['POST'])]
    public function updateSettingAction(Request $request, int $id): Response
    {
        // Fetch store with settings
        $store = $this->StoreRepository->createQueryBuilder('s')
            ->leftJoin('s.setting', 'st')
            ->leftJoin('st.mondayHours', 'mh')
            ->leftJoin('st.tuesdayHours', 'th')
            ->leftJoin('st.wednesdayHours', 'wh')
            ->leftJoin('st.thursdayHours', 'thh')
            ->leftJoin('st.fridayHours', 'fh')
            ->leftJoin('st.saturdayHours', 'sah')
            ->leftJoin('st.sundayHours', 'suh')
            ->addSelect('st', 'mh', 'th', 'wh', 'thh', 'fh', 'sah', 'suh')
            ->where('s.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$store) {
            throw $this->createNotFoundException('Store not found');
        }

        // Initialize StoreSetting if it doesn't exist
        if (!$store->getSetting()) {
            $storeSetting = new \App\Entity\StoreSetting();
            $store->setSetting($storeSetting);
            $this->entityManager->persist($storeSetting);
            $this->entityManager->flush();
        }

        $storeSetting = $store->getSetting();
        
        // Ensure all BusinessHours are initialized (they can be null)
        if ($storeSetting) {
            $needsFlush = false;
            // Check and initialize each BusinessHours if null
            $hoursMapping = [
                'mondayHours', 'tuesdayHours', 'wednesdayHours', 'thursdayHours',
                'fridayHours', 'saturdayHours', 'sundayHours'
            ];
            
            foreach ($hoursMapping as $property) {
                $getter = 'get' . ucfirst($property);
                $setter = 'set' . ucfirst($property);
                $hours = $storeSetting->$getter();
                
                if (!$hours) {
                    // Create default BusinessHours based on day
                    if ($property === 'sundayHours') {
                        $newHours = new \App\Entity\BusinessHours(null, null, true); // Closed
                    } else {
                        $newHours = new \App\Entity\BusinessHours('09:00', '18:00', false);
                    }
                    $storeSetting->$setter($newHours);
                    $needsFlush = true;
                }
            }
            
            if ($needsFlush) {
                $this->entityManager->persist($storeSetting);
                $this->entityManager->flush();
                // Refresh the entity to ensure all relationships are loaded
                $this->entityManager->refresh($storeSetting);
            }
        }
        
        // Store references to existing BusinessHours IDs before form processing
        $existingHoursIds = [];
        $hoursMapping = [
            'mondayHours' => 'getMondayHours',
            'tuesdayHours' => 'getTuesdayHours',
            'wednesdayHours' => 'getWednesdayHours',
            'thursdayHours' => 'getThursdayHours',
            'fridayHours' => 'getFridayHours',
            'saturdayHours' => 'getSaturdayHours',
            'sundayHours' => 'getSundayHours',
        ];
        
        foreach ($hoursMapping as $property => $getter) {
            $hours = $storeSetting->$getter();
            if ($hours && $hours->getId()) {
                $existingHoursIds[$property] = $hours->getId();
            }
        }
        
        $form = $this->createForm(StoreSettingType::class, $storeSetting);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // After form processing, ensure we're updating existing entities, not creating new ones
            // If the form created new BusinessHours entities, we need to restore the existing ones
            $this->restoreExistingBusinessHours($storeSetting, $existingHoursIds);
            
            // Now update the existing BusinessHours with form data
            $this->updateBusinessHoursFromForm($storeSetting, $form);
            
            $this->entityManager->flush();
            $this->addSuccessToast('Store settings updated!', 'The store settings have been successfully updated.');

            if ($request->headers->has('turbo-frame')) {
                $stream = $this->renderBlockView("admin/store/edit.html.twig", 'stream_success', [
                    'store' => $store
                ]);
                $this->addFlash('stream', $stream);
            }

            return $this->redirectToRoute('admin_store_edit', ['id' => $id], Response::HTTP_SEE_OTHER);
        }

        // If form has errors, redirect back to edit page
        return $this->redirectToRoute('admin_store_edit', ['id' => $id], Response::HTTP_SEE_OTHER);
    }

    private function updateBusinessHoursFromForm(\App\Entity\StoreSetting $storeSetting, $form): void
    {
        $hoursMapping = [
            'mondayHours' => ['getMondayHours', 'setMondayHours'],
            'tuesdayHours' => ['getTuesdayHours', 'setTuesdayHours'],
            'wednesdayHours' => ['getWednesdayHours', 'setWednesdayHours'],
            'thursdayHours' => ['getThursdayHours', 'setThursdayHours'],
            'fridayHours' => ['getFridayHours', 'setFridayHours'],
            'saturdayHours' => ['getSaturdayHours', 'setSaturdayHours'],
            'sundayHours' => ['getSundayHours', 'setSundayHours'],
        ];

        foreach ($hoursMapping as $property => $methods) {
            $getter = $methods[0];
            $setter = $methods[1];
            
            $dayForm = $form->get($property);
            if (!$dayForm) {
                continue;
            }
            
            // Get the existing BusinessHours entity (from database)
            $existingHours = $storeSetting->$getter();
            
            // If existing hours don't exist, create new ones
            if ($existingHours === null) {
                $existingHours = new \App\Entity\BusinessHours();
                $storeSetting->$setter($existingHours);
                $this->entityManager->persist($existingHours);
            }
            
            // Get form data
            $isClosed = $dayForm->get('isClosed')->getData() ?? false;
            $openTime = $dayForm->get('openTime')->getData();
            $closeTime = $dayForm->get('closeTime')->getData();
            
            // Update existing BusinessHours properties (don't create new entity)
            $existingHours->setIsClosed($isClosed);
            $existingHours->setOpenTime($openTime);
            $existingHours->setCloseTime($closeTime);
            
            // Note: We don't need to persist if the entity is already managed (has an ID)
            // The entity manager will track changes automatically
        }
    }

    private function restoreExistingBusinessHours(\App\Entity\StoreSetting $storeSetting, array $existingHoursIds): void
    {
        // This method restores existing BusinessHours entities if the form created new ones
        $hoursMapping = [
            'mondayHours' => ['getMondayHours', 'setMondayHours'],
            'tuesdayHours' => ['getTuesdayHours', 'setTuesdayHours'],
            'wednesdayHours' => ['getWednesdayHours', 'setWednesdayHours'],
            'thursdayHours' => ['getThursdayHours', 'setThursdayHours'],
            'fridayHours' => ['getFridayHours', 'setFridayHours'],
            'saturdayHours' => ['getSaturdayHours', 'setSaturdayHours'],
            'sundayHours' => ['getSundayHours', 'setSundayHours'],
        ];

        foreach ($hoursMapping as $property => $methods) {
            $getter = $methods[0];
            $setter = $methods[1];
            $hours = $storeSetting->$getter();
            
            // If we have an existing ID for this day but the current entity doesn't have one,
            // it means a new entity was created by the form
            if (isset($existingHoursIds[$property]) && ($hours === null || $hours->getId() === null || $hours->getId() !== $existingHoursIds[$property])) {
                // Find the existing BusinessHours entity from database
                $existingHours = $this->entityManager->find(\App\Entity\BusinessHours::class, $existingHoursIds[$property]);
                if ($existingHours) {
                    // Replace the new entity with the existing one
                    $storeSetting->$setter($existingHours);
                }
            }
        }
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
