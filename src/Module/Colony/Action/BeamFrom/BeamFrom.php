<?php

declare(strict_types=1);

namespace Stu\Module\Colony\Action\BeamFrom;

use Override;
use request;
use Stu\Component\Ship\System\ShipSystemTypeEnum;
use Stu\Lib\Information\InformationWrapper;
use Stu\Lib\Transfer\BeamUtilInterface;
use Stu\Module\Colony\Lib\ColonyLoaderInterface;
use Stu\Module\Colony\View\ShowColony\ShowColony;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\TargetLink;
use Stu\Module\Message\Lib\PrivateMessageFolderTypeEnum;
use Stu\Module\Message\Lib\PrivateMessageSenderInterface;
use Stu\Module\Ship\Lib\Interaction\InteractionCheckerInterface;
use Stu\Module\Ship\Lib\ShipLoaderInterface;
use Stu\Module\Ship\View\ShowShip\ShowShip;

final class BeamFrom implements ActionControllerInterface
{
    public const string ACTION_IDENTIFIER = 'B_BEAMFROM';

    public function __construct(private ColonyLoaderInterface $colonyLoader, private BeamUtilInterface $beamUtil, private ShipLoaderInterface $shipLoader, private InteractionCheckerInterface $interactionChecker, private PrivateMessageSenderInterface $privateMessageSender)
    {
    }

    #[Override]
    public function handle(GameControllerInterface $game): void
    {
        $game->setView(ShowColony::VIEW_IDENTIFIER);

        $userId = $game->getUser()->getId();

        $colony = $this->colonyLoader->loadWithOwnerValidation(
            request::indInt('id'),
            $userId
        );

        if ($colony->getEps() == 0) {
            $game->addInformation(_('Keine Energie vorhanden'));
            return;
        }
        $wrapper = $this->shipLoader->find(request::postIntFatal('target'));
        if ($wrapper === null) {
            return;
        }

        $ship = $wrapper->get();

        if (!$this->interactionChecker->checkColonyPosition($colony, $ship)) {
            return;
        }

        if ($ship->getUser()->isVacationRequestOldEnough()) {
            $game->addInformation(_('Aktion nicht möglich, der Spieler befindet sich im Urlaubsmodus!'));
            return;
        }

        if (
            $ship->getShieldState()
            && $ship->getUser()->getId() !== $userId
        ) {
            $game->addInformationf(_('Die %s hat die Schilde aktiviert'), $ship->getName());
            return;
        }
        if (
            $ship->isSystemHealthy(ShipSystemTypeEnum::SYSTEM_BEAM_BLOCKER)
            && $ship->getUser()->getId() !== $userId
        ) {
            $game->addInformation(sprintf(_('Die %s hat einen Beamblocker aktiviert. Transfer nicht möglich.'), $ship->getName()));
            return;
        }
        if (!$colony->storagePlaceLeft()) {
            $game->addInformationf(_('Der Lagerraum der %s ist voll'), $colony->getName());
            return;
        }
        $commodities = request::postArray('commodities');
        $gcount = request::postArray('count');

        $shipStorage = $ship->getStorage();

        if ($shipStorage->isEmpty()) {
            $game->addInformation(_('Keine Waren zum Beamen vorhanden'));
            return;
        }
        if (count($commodities) == 0 || count($gcount) == 0) {
            $game->addInformation(_('Es wurden keine Waren zum Beamen ausgewählt'));
            return;
        }

        if ($game->getUser() === $ship->getUser()) {
            $game->setTargetLink(new TargetLink(
                sprintf("ship.php?%s=1&id=%d", ShowShip::VIEW_IDENTIFIER, $ship->getId()),
                'Zum Schiff wechseln'
            ));
        }

        $informations = new InformationWrapper();

        foreach ($commodities as $key => $value) {
            $commodityId = (int) $value;

            if (!array_key_exists($key, $gcount)) {
                continue;
            }

            $this->beamUtil->transferCommodity(
                $commodityId,
                $gcount[$key],
                $colony,
                $ship,
                $colony,
                $informations
            );
        }

        if (!$informations->isEmpty()) {
            $informations->addInformationArray(
                [sprintf(
                    _('Die Kolonie %s hat folgende Waren von der %s transferiert'),
                    $colony->getName(),
                    $ship->getName()
                )],
                true
            );

            $this->privateMessageSender->send(
                $userId,
                $ship->getUser()->getId(),
                $informations->getInformationsAsString(),
                PrivateMessageFolderTypeEnum::SPECIAL_TRADE,
                sprintf('ship.php?%s=1&id=%d', ShowShip::VIEW_IDENTIFIER, $ship->getId())
            );
        }



        $game->addInformationWrapper($informations);
    }

    #[Override]
    public function performSessionCheck(): bool
    {
        return true;
    }
}
