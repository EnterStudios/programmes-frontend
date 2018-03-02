<?php
declare(strict_types = 1);

namespace App\Controller\Traits;

use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeContainer;
use BBC\ProgrammesPagesService\Service\CollapsedBroadcastsService;

trait IndexerTrait
{
    protected function getUpcomingBroadcastsIndexedByProgrammePid(
        ProgrammeContainer $programme,
        CollapsedBroadcastsService $collapsedBroadcastsService
    ): array {
        $broadcasts = $collapsedBroadcastsService->findUpcomingByProgrammeWithFullServicesOfNetworksList($programme);
        $upcoming = [];

        foreach ($broadcasts as $broadcast) {
            $upcoming[(string) $broadcast->getProgrammeItem()->getPid()] = $broadcast;
        }

        return $upcoming;
    }
}
