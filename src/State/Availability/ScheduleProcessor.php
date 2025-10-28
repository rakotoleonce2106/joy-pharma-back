<?php

namespace App\State\Availability;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\ScheduleInput;
use App\Entity\DeliverySchedule;
use App\Entity\User;
use App\Repository\DeliveryScheduleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ScheduleProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly DeliveryScheduleRepository $scheduleRepository,
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

        /** @var ScheduleInput $input */
        $input = $data;

        // Remove existing schedules
        $existingSchedules = $this->scheduleRepository->findByDeliveryPerson($user);
        foreach ($existingSchedules as $schedule) {
            $this->em->remove($schedule);
        }

        // Create new schedules
        foreach ($input->schedules as $scheduleData) {
            $schedule = new DeliverySchedule();
            $schedule->setDeliveryPerson($user);
            $schedule->setDayOfWeek($scheduleData->dayOfWeek);
            $schedule->setStartTime(new \DateTime($scheduleData->startTime));
            $schedule->setEndTime(new \DateTime($scheduleData->endTime));
            $schedule->setIsActive($scheduleData->isActive);
            
            $this->em->persist($schedule);
        }

        $this->em->flush();

        return $this->scheduleRepository->findByDeliveryPerson($user);
    }
}





