<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Lib\Crew;

use Stu\Component\Ship\Crew\ShipCrewCalculatorInterface;
use Stu\Orm\Entity\ColonyInterface;
use Stu\Orm\Entity\ShipCrewInterface;
use Stu\Orm\Entity\ShipInterface;
use Stu\Orm\Entity\UserInterface;
use Stu\Orm\Repository\ShipTakeoverRepositoryInterface;

final class TroopTransferUtility implements TroopTransferUtilityInterface
{
    private ShipTakeoverRepositoryInterface $shipTakeoverRepository;

    private ShipCrewCalculatorInterface $shipCrewCalculator;

    public function __construct(
        ShipTakeoverRepositoryInterface $shipTakeoverRepository,
        ShipCrewCalculatorInterface $shipCrewCalculator
    ) {
        $this->shipTakeoverRepository = $shipTakeoverRepository;
        $this->shipCrewCalculator = $shipCrewCalculator;
    }

    public function getFreeQuarters(ShipInterface $ship): int
    {
        $free = $this->shipCrewCalculator->getMaxCrewCountByShip($ship) - $ship->getCrewCount();

        return max(0, $free);
    }

    public function getBeamableTroopCount(ShipInterface $ship): int
    {
        $buildplan = $ship->getBuildplan();
        if ($buildplan === null) {
            return 0;
        }

        $free = $ship->getCrewCount() - $buildplan->getCrew();

        return max(0, $free);
    }

    public function ownCrewOnTarget(UserInterface $user, ShipInterface $ship): int
    {
        $count = 0;

        foreach ($ship->getCrewAssignments() as $shipCrew) {
            if ($shipCrew->getCrew()->getUser() === $user) {
                $count++;
            }
        }

        return $count;
    }

    public function foreignerCount(ShipInterface $ship): int
    {
        $count = 0;

        $user = $ship->getUser();

        foreach ($ship->getCrewAssignments() as $shipCrew) {
            if ($shipCrew->getCrew()->getUser() !== $user) {
                $count++;
            }
        }

        return $count;
    }

    public function assignCrew(ShipCrewInterface $crewAssignment, ShipInterface|ColonyInterface $target): void
    {
        $ship = $crewAssignment->getShip();
        if ($ship !== null) {
            $ship->getCrewAssignments()->removeElement($crewAssignment);
        }

        $colony = $crewAssignment->getColony();
        if ($colony !== null) {
            $colony->getCrewAssignments()->removeElement($crewAssignment);
        }

        $target->getCrewAssignments()->add($crewAssignment);

        if ($target instanceof ColonyInterface) {
            $crewAssignment
                ->setColony($target)
                ->setShip(null)
                ->setSlot(null);
        } else {
            // TODO create CrewSlotAssignment

            $crewAssignment
                ->setShip($target)
                ->setColony(null)
                ->setSlot(null);

            // clear any ShipTakeover
            $takeover = $target->getTakeover();
            if ($takeover !== null) {
                $this->shipTakeoverRepository->delete($takeover);
            }
        }
    }
}
