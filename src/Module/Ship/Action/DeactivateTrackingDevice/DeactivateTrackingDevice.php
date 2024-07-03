<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Action\DeactivateTrackingDevice;

use Override;
use request;

use Stu\Component\Ship\System\ShipSystemTypeEnum;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Ship\Lib\ActivatorDeactivatorHelperInterface;
use Stu\Module\Ship\Lib\ShipLoaderInterface;
use Stu\Module\Ship\View\ShowShip\ShowShip;

final class DeactivateTrackingDevice implements ActionControllerInterface
{
    public const string ACTION_IDENTIFIER = 'B_DEACTIVATE_TRACKER';

    public function __construct(private ShipLoaderInterface $shipLoader, private ActivatorDeactivatorHelperInterface $helper)
    {
    }

    #[Override]
    public function handle(GameControllerInterface $game): void
    {
        $game->setView(ShowShip::VIEW_IDENTIFIER);

        $userId = $game->getUser()->getId();

        $wrapper = $this->shipLoader->getWrapperByIdAndUser(
            request::indInt('id'),
            $userId
        );

        $tracker = $wrapper->getTrackerSystemData();

        if ($tracker === null || $tracker->targetId === null) {
            return;
        }
        $this->helper->deactivate(request::indInt('id'), ShipSystemTypeEnum::SYSTEM_TRACKER, $game);
    }

    #[Override]
    public function performSessionCheck(): bool
    {
        return true;
    }
}
