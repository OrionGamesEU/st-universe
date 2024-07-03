<?php

declare(strict_types=1);

namespace Stu\Orm\Repository;

use Override;
use Doctrine\ORM\EntityRepository;
use Stu\Orm\Entity\ShipRumpSpecial;

/**
 * @extends EntityRepository<ShipRumpSpecial>
 */
final class ShipRumpSpecialRepository extends EntityRepository implements ShipRumpSpecialRepositoryInterface
{
    #[Override]
    public function getByShipRump(int $shipRumpId): array
    {
        return $this->findBy([
            'rumps_id' => $shipRumpId
        ]);
    }
}
