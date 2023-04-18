<?php

declare(strict_types=1);

namespace Stu\Module\Maindesk\Action\ColonizationShip;

use Stu\Exception\AccessViolation;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\PlayerSetting\Lib\UserEnum;
use Stu\Component\Ship\ShipEnum;
use Stu\Module\Ship\Lib\ShipCreatorInterface;
use Stu\Orm\Repository\UserRepositoryInterface;

final class ColonizationShip implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_COLONIZATION_SHIP';

    private ShipCreatorInterface $shipCreator;

    private UserRepositoryInterface $userRepository;

    public function __construct(
        ShipCreatorInterface $shipCreator,
        UserRepositoryInterface $userRepository
    ) {
        $this->shipCreator = $shipCreator;
        $this->userRepository = $userRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $user = $game->getUser();

        if ((int) $user->getState() !== 1) {
            throw new AccessViolation();
        }

        if ((int) $user->getFactionId() == 1) {
            $rumpId = ShipEnum::FED_COL_RUMP;
            $buildplanId = ShipEnum::FED_COL_BUILDPLAN;
        }

        if ((int) $user->getFactionId() == 2) {
            $rumpId = ShipEnum::ROM_COL_RUMP;
            $buildplanId = ShipEnum::ROM_COL_BUILDPLAN;
        }

        if ((int) $user->getFactionId() == 3) {
            $rumpId = ShipEnum::KLING_COL_RUMP;
            $buildplanId = ShipEnum::KLING_COL_BUILDPLAN;
        }

        if ((int) $user->getFactionId() == 4) {
            $rumpId = ShipEnum::CARD_COL_RUMP;
            $buildplanId = ShipEnum::CARD_COL_BUILDPLAN;
        }

        if ((int) $user->getFactionId() == 5) {
            $rumpId = ShipEnum::FERG_COL_RUMP;
            $buildplanId = ShipEnum::FERG_COL_BUILDPLAN;
        }

        $wrapper = $this->shipCreator->createBy(
            $user->getId(),
            $rumpId,
            $buildplanId
        );

        $ship = $wrapper->get();

        $ship->updateLocation($user->getFaction()->getStartMap(), null);
        $ship->setReactorLoad((int)floor($ship->getReactorCapacity() * 20));
        $ship->setCanBeDisabled(true);
        $ship->setSensorRange(5);
        $ship->setReactorOutput((int)floor($ship->getReactorOutput() * 10));

        $eps = $wrapper->getEpsSystemData();
        $eps->setEps((int)floor($eps->getTheoreticalMaxEps() * 10))->update();
        $eps->setMaxEps((int)floor($eps->getTheoreticalMaxEps() * 10))->update();

        $user->setState(UserEnum::USER_STATE_COLONIZATION_SHIP);
        $this->userRepository->save($user);

        $game->redirectTo('./ship.php');
    }

    public function performSessionCheck(): bool
    {
        return false;
    }
}
