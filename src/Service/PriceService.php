<?php

namespace App\Service;

use App\Entity\Price;
use App\Repository\PriceRepository;
use Doctrine\ORM\EntityManagerInterface;


readonly class PriceService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private PriceRepository        $PriceRepository
    )
    {
    }

    public function createPrice(Price $price): void
    {
        $this->manager->persist($price);
    }


    public function updatePrice(Price $price): void
    {
        $this->manager->flush();
    }

    public function batchDeleteCategories(array $priceIds): void
    {


        foreach ($priceIds as $id) {
            $price = $this->PriceRepository->find($id);
            if ($price) {
                $this->deletePrice($price);

            }
        }


    }

    public function deletePrice(Price $price): void
    {
        $this->manager->remove($price);
        $this->manager->flush();
    }
}
