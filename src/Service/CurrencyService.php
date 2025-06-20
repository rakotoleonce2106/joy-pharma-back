<?php

namespace App\Service;

use App\Entity\Currency;
use App\Repository\CurrencyRepository;
use Doctrine\ORM\EntityManagerInterface;


readonly class CurrencyService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private CurrencyRepository     $currencyRepository
    )
    {
    }

    public function getOrCreateCurrency(String $label): ?Currency
    {
        $currency = $this->currencyRepository->findOneBy(['label' => $label]);
        if ($currency) {
            return null;
        }
        $currency = new Currency();
        $currency->setLabel($label);
        $this->manager->persist($currency);
        return $currency;
    }


    public function updateCurrency(Currency $currency): void
    {
        $this->manager->flush();
    }

    public function batchDeleteCategories(array $CurrencyIds): void
    {


        foreach ($CurrencyIds as $id) {
            $currency = $this->currencyRepository->find($id);
            if ($currency) {
                $this->deleteCurrency($currency);

            }
        }


    }

    public function deleteCurrency(Currency $currency): void
    {
        $this->manager->remove($currency);
        $this->manager->flush();
    }
}
