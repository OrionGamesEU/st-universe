<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Lib\Movement\Route;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use InvalidArgumentException;
use Stu\Orm\Entity\MapInterface;
use Stu\Orm\Entity\StarSystemMapInterface;
use Stu\Orm\Repository\MapRepositoryInterface;
use Stu\Orm\Repository\StarSystemMapRepositoryInterface;

final class LoadWaypoints implements LoadWaypointsInterface
{
    private MapRepositoryInterface $mapRepository;

    private StarSystemMapRepositoryInterface $starSystemMapRepository;

    public function __construct(
        MapRepositoryInterface $mapRepository,
        StarSystemMapRepositoryInterface $starSystemMapRepository
    ) {
        $this->mapRepository = $mapRepository;
        $this->starSystemMapRepository = $starSystemMapRepository;
    }

    public function load(
        MapInterface|StarSystemMapInterface $start,
        MapInterface|StarSystemMapInterface $destination
    ): Collection {
        if ($start instanceof MapInterface !== $destination instanceof MapInterface) {
            throw new InvalidArgumentException('start and destination have different type');
        }

        $startX = $start->getX();
        $startY = $start->getY();

        $destinationX = $destination->getX();
        $destinationY = $destination->getY();

        $sortAscending = true;

        if ($startY > $destinationY) {
            $sortAscending = false;
        }
        if ($startX > $destinationX) {
            $sortAscending = false;
        }
        if ($start instanceof MapInterface) {
            $waypoints = $this->mapRepository->getByCoordinateRange(
                $start->getLayer()->getId(),
                $startX,
                $destinationX,
                $startY,
                $destinationY,
                $sortAscending
            );
        } else {
            $waypoints = $this->starSystemMapRepository->getByCoordinateRange(
                $start->getSystem(),
                $startX,
                $destinationX,
                $startY,
                $destinationY,
                $sortAscending
            );
        }

        $result = new ArrayCollection();

        foreach ($waypoints as $waypoint) {
            if ($waypoint !== $start) {
                $result->add($waypoint);
            }
        }

        return $result;
    }
}
