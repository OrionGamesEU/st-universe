<?php

declare(strict_types=1);

namespace Stu\Module\Database\View\ResearchRanking;

use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;
use Stu\Module\Database\Lib\DatabaseTopListWithPoints;
use Stu\Module\Database\Lib\DatabaseUiFactoryInterface;
use Stu\Orm\Repository\ResearchedRepositoryInterface;

final class ResearchRanking implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'SHOW_TOP_RESEARCH';

    private ResearchedRepositoryInterface $researchedRepository;

    private DatabaseUiFactoryInterface $databaseUiFactory;

    public function __construct(
        ResearchedRepositoryInterface $researchedRepository,
        DatabaseUiFactoryInterface $databaseUiFactory
    ) {
        $this->researchedRepository = $researchedRepository;
        $this->databaseUiFactory = $databaseUiFactory;
    }

    public function handle(GameControllerInterface $game): void
    {
        $game->appendNavigationPart(
            'database.php',
            'Datenbank'
        );
        $game->appendNavigationPart(
            sprintf(
                'database.php?%s=1',
                static::VIEW_IDENTIFIER
            ),
            'Die 10 besten Forscher'
        );
        $game->setPageTitle('/ Datenbank / Die 10 besten Forscher');
        $game->showMacro('html/database.xhtml/top_research_user');

        $userPoints = 0;
        $list = $this->researchedRepository->getResearchedPoints();
        foreach ($list as $data) {
            if ($data['user_id'] == $game->getUser()->getId()) {
                $userPoints = (int)$data['points'];
            }
        }
        $game->setTemplateVar('USER_POINTS',  $userPoints);

        $game->setTemplateVar(
            'RESEARCH_LIST',
            array_map(
                fn (array $data): DatabaseTopListWithPoints => $this->databaseUiFactory->createDatabaseTopListWithPoints($data['user_id'], (string) $data['points']),
                array_slice($list, 0, 10)
            )
        );
    }
}
