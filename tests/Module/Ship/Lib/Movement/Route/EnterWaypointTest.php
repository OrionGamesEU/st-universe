<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Lib\Movement\Route;

use Mockery\MockInterface;
use Override;
use Stu\Module\Control\StuTime;
use Stu\Orm\Entity\MapInterface;
use Stu\Orm\Entity\ShipInterface;
use Stu\Orm\Entity\StarSystemMapInterface;
use Stu\Orm\Entity\WormholeEntryInterface;
use Stu\Orm\Repository\WormholeEntryRepositoryInterface;
use Stu\StuTestCase;

class EnterWaypointTest extends StuTestCase
{
    /** @var MockInterface&WormholeEntryRepositoryInterface */
    private MockInterface $wormholeEntryRepository;

    /** @var MockInterface&StuTime */
    private MockInterface $stuTime;

    private EnterWaypointInterface $subject;

    #[Override]
    protected function setUp(): void
    {
        $this->wormholeEntryRepository = $this->mock(WormholeEntryRepositoryInterface::class);
        $this->stuTime = $this->mock(StuTime::class);

        $this->subject = new EnterWaypoint(
            $this->wormholeEntryRepository,
            $this->stuTime
        );
    }

    public function testEnterNextWaypointExpectLocationUpdateWhenOnMap(): void
    {
        $ship = $this->mock(ShipInterface::class);
        $waypoint = $this->mock(MapInterface::class);

        $ship->shouldReceive('setLocation')
            ->with($waypoint)
            ->once();

        $this->subject->enterNextWaypoint(
            $ship,
            false,
            $waypoint,
            null
        );
    }

    public function testEnterNextWaypointExpectWormholeEntryUsing(): void
    {
        $ship = $this->mock(ShipInterface::class);
        $waypoint = $this->mock(MapInterface::class);
        $wormholeEntry = $this->mock(WormholeEntryInterface::class);

        $ship->shouldReceive('setLocation')
            ->with($waypoint)
            ->once();

        $this->stuTime->shouldReceive('time')
            ->withNoArgs()
            ->once()
            ->andReturn(42);

        $wormholeEntry->shouldReceive('setLastUsed')
            ->with(42)
            ->once();

        $this->wormholeEntryRepository->shouldReceive('save')
            ->with($wormholeEntry)
            ->once();

        $this->subject->enterNextWaypoint(
            $ship,
            false,
            $waypoint,
            $wormholeEntry
        );
    }

    public function testEnterNextWaypointExpectLocationUpdateWhenOnSystemMap(): void
    {
        $ship = $this->mock(ShipInterface::class);
        $waypoint = $this->mock(StarSystemMapInterface::class);

        $ship->shouldReceive('setLocation')
            ->with($waypoint)
            ->once();

        $this->subject->enterNextWaypoint(
            $ship,
            false,
            $waypoint,
            null
        );
    }

    public function testEnterNextWaypointExpectFlightDirectionUpdateAndSignatureCreationWhenTraversing(): void
    {
        $ship = $this->mock(ShipInterface::class);
        $waypoint = $this->mock(StarSystemMapInterface::class);

        $ship->shouldReceive('setLocation')
            ->with($waypoint)
            ->once();

        $this->subject->enterNextWaypoint(
            $ship,
            true,
            $waypoint,
            null
        );
    }
}
