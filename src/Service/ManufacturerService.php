<?php

namespace App\Service;

use App\Entity\Manufacturer;
use App\Entity\Product;
use App\Repository\ManufacturerRepository;
use Doctrine\ORM\EntityManagerInterface;


readonly class ManufacturerService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private ManufacturerRepository $manufacturerRepository
    )
    {
    }

    public function createManufacturer(Manufacturer $manufacturer): void
    {
        $this->manager->persist($manufacturer);
        $this->manager->flush();
    }


    public function getOrCreateManufacturerByName(string $name): Manufacturer
    {
        $manufacturer = $this->manufacturerRepository->findOneBy(['name' => $name]);
        if (!$manufacturer) {
            $manufacturer = new Manufacturer();
            $manufacturer->setName($name);
            $this->manager->persist($manufacturer);
        }
        return $manufacturer;
    }


    public function updateManufacturer(Manufacturer $Manufacturer): void
    {
        $this->manager->flush();
    }

    public function findByName(string $name): ?Manufacturer
    {
        return $this->manager->getRepository(Manufacturer::class)
            ->findOneBy(['name' => $name]);
    }

    public function batchDeleteManufacturers(array $manufacturerIds): array
    {
        $successCount = 0;
        $failureCount = 0;

        if (empty($manufacturerIds)) {
            return [
                'success_count' => 0,
                'failure_count' => 0
            ];
        }

        foreach ($manufacturerIds as $id) {
            try {
                $manufacturer = $this->manufacturerRepository->find($id);
                if ($manufacturer) {
                    $this->deleteManufacturerRelations($manufacturer);
                    $this->manager->remove($manufacturer);
                    $successCount++;
                } else {
                    $failureCount++;
                }
            } catch (\Exception $e) {
                $failureCount++;
                error_log('Failed to delete manufacturer with id ' . $id . ': ' . $e->getMessage());
            }
        }

        try {
            $this->manager->flush();
        } catch (\Exception $e) {
            error_log('Failed to flush manufacturer deletions: ' . $e->getMessage());
            throw $e;
        }

        return [
            'success_count' => $successCount,
            'failure_count' => $failureCount
        ];
    }

    public function deleteManufacturer(Manufacturer $manufacturer): void
    {
        $this->deleteManufacturerRelations($manufacturer);
        $this->manager->remove($manufacturer);
        $this->manager->flush();
    }

    /**
     * Remove all product associations before deleting the manufacturer
     */
    private function deleteManufacturerRelations(Manufacturer $manufacturer): void
    {
        // Find all products with this manufacturer and set manufacturer to null
        $products = $this->manager->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->where('p.manufacturer = :manufacturer')
            ->setParameter('manufacturer', $manufacturer)
            ->getQuery()
            ->getResult();

        foreach ($products as $product) {
            $product->setManufacturer(null);
        }
    }
}
