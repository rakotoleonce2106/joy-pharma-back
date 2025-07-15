<?php
// api/src/State/OrderCreateProcessor.php

namespace App\State\Products;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Order;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private ValidatorInterface $validator,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Order
    {
        if (!$data instanceof Order || !$operation instanceof Post) {
            return $this->processor->process($data, $operation, $uriVariables, $context);
        }

        $user = $this->security->getUser();

        $violations = $this->validator->validate($user, null, ['Default', 'order:create']);
        if (count($violations) > 0) {
            throw new BadRequestHttpException('Validation failed: ' . (string) $violations);
        }

        $data->setOwner($user);

        foreach ($data->getItems() as $item) {
            $item->setOrder($data);
            $item->setTotalPrice($item->getProduct()->getTotalPrice() * $item->getQuantity());
        }

        return $this->processor->process($data, $operation, $uriVariables, $context);
    }
}
