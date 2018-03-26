<?php
declare(strict_types = 1);

namespace App\DsShared\Helpers;

use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeItem;

class StreamUrlHelper
{
    public function getRouteForProgrammeItem(ProgrammeItem $programmeItem): string
    {
        if ($programmeItem instanceof Episode && $programmeItem->isVideo()) {
            return 'iplayer_play'; // To iPlayer
        }

        return 'find_by_pid'; // To Programmes page (which is where clip/radio playout will occur)
    }
}
