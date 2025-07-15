<?php

namespace App\Service;

use App\Entity\Currency;
use App\Repository\CurrencyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;


readonly class CurrencyService
{

    public function __construct(
        private EntityManagerInterface $manager,
        private CurrencyRepository     $currencyRepository,
        private LoggerInterface $logger
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

    public function getCurrency(): ?Currency
    {
        return $this->currencyRepository->findOneBy(['label' => 'Ar']);

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

    private const MINIMUM_AMOUNTS = [
        'USD' => 50,
        'EUR' => 50,
        'GBP' => 30,
        'AUD' => 50,
        'CAD' => 50,
        'CHF' => 50,
        'DKK' => 250,
        'NOK' => 300,
        'SEK' => 300,
        'JPY' => 50,
        'MXN' => 1000,
        'BRL' => 50,
        'HKD' => 400,
        'SGD' => 50,
        'MYR' => 200,
        'Ar' => 20000, // Assuming 1 Ar = 100 centimes and minimum is around 0.50 EUR
    ];

    private const DEFAULT_MINIMUM_AMOUNT = 50;



    public function convertToCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    public function validateAmount(int $amount, string $currency): void
    {
        $minAmount = $this->getMinimumAmount($currency);
        if ($amount < $minAmount) {
            $this->logger->warning('Amount below minimum', [
                'amount' => $amount,
                'currency' => $currency,
                'minimum' => $minAmount
            ]);
            throw new BadRequestHttpException(sprintf('Amount must be at least %d cents for currency %s', $minAmount, $currency));
        }
    }

    public function getMinimumAmount(string $currency): int
    {
        if (!isset(self::MINIMUM_AMOUNTS[$currency])) {
            $this->logger->warning('Unknown currency, using default minimum amount', ['currency' => $currency]);
            return self::DEFAULT_MINIMUM_AMOUNT;
        }

        return self::MINIMUM_AMOUNTS[$currency];
    }

    public function isSupportedCurrency(string $currency): bool
    {
        return isset(self::MINIMUM_AMOUNTS[$currency]);
    }

    public function convertFromCents(int $amountInCents): float
    {
        return $amountInCents / 100;
    }
}
