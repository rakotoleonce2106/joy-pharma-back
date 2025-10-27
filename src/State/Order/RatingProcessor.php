<?php

namespace App\State\Order;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\RatingInput;
use App\Entity\Rating;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\RatingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RatingProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly RatingRepository $ratingRepository,
        private readonly EntityManagerInterface $em,
        private readonly Security $security
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException('User not found');
        }

        $orderId = $uriVariables['id'] ?? null;
        if (!$orderId) {
            throw new NotFoundHttpException('Order ID not provided');
        }

        $order = $this->orderRepository->find($orderId);

        if (!$order) {
            throw new NotFoundHttpException('Order not found');
        }

        // Check if order is delivered
        if (!$order->getDeliveredAt()) {
            throw new BadRequestHttpException('Order must be delivered before rating');
        }

        // Check if already rated
        if ($order->getRating()) {
            throw new BadRequestHttpException('Order already rated');
        }

        // Check if user is the customer
        if ($order->getOwner() !== $user) {
            throw new AccessDeniedHttpException('Only the customer can rate the delivery');
        }

        $deliveryPerson = $order->getDeliver();
        if (!$deliveryPerson) {
            throw new BadRequestHttpException('No delivery person assigned');
        }

        /** @var RatingInput $input */
        $input = $data;

        // Create rating
        $rating = new Rating();
        $rating->setOrderRef($order);
        $rating->setDeliveryPerson($deliveryPerson);
        $rating->setCustomer($user);
        $rating->setRating($input->rating);
        $rating->setComment($input->comment);

        $this->em->persist($rating);
        $this->em->flush();

        // Update delivery person's average rating
        $avgRating = $this->ratingRepository->getAverageRatingForDeliveryPerson($deliveryPerson);
        $deliveryPerson->setAverageRating($avgRating);
        $this->em->flush();

        return [
            'success' => true,
            'message' => 'Rating submitted successfully',
            'data' => [
                'rating' => $rating->getRating(),
                'comment' => $rating->getComment()
            ]
        ];
    }
}


