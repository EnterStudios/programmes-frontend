<?php
declare(strict_types=1);

namespace App\DsShared\Helpers;

use BBC\ProgrammesPagesService\Domain\Entity\Version;

class CanonicalVersionHelper
{
    const STREAMABLE_WEIGHT = 10000;

    const VERSION_PREFERENCE_WEIGHTINGS = [
        'Editorial' => 20,
        'Original' => 10,
        'Podcast' => -5,
        'AudioDescribed' => -10,
        'DubbedAudioDescribed' => -10,
        'OpenSubtitled' => -10,
        'Signed' => -10,
    ];

    public function getCanonicalVersion(array $versions): Version
    {
        uasort($versions, function (Version $a, Version $b) {
            $weightingA = $this->typeWeighting($a);
            $weightingB = $this->typeWeighting($b);

            // Add a large weight to streamable versions so they're preferred
            if ($a->isStreamable()) {
                $weightingA += self::STREAMABLE_WEIGHT;
            }

            if ($b->isStreamable()) {
                $weightingB += self::STREAMABLE_WEIGHT;
            }

            // should be ordered by descending, so B first
            return $weightingB <=> $weightingA;
        });

        return reset($versions);
    }

    private function typeWeighting(Version $version)
    {
        // go through each of the version types,
        // choosing the highest weighting value.
        $highest = null;
        foreach ($version->getVersionTypes() as $versionType) {
            $type = $versionType->getType();
            $weighting = 0;

            if (array_key_exists($type, self::VERSION_PREFERENCE_WEIGHTINGS)) {
                $weighting = self::VERSION_PREFERENCE_WEIGHTINGS[$type];
            }

            if (is_null($highest)) {
                $highest = $weighting;
            }

            if ($weighting > $highest) {
                $highest = $weighting;
            }
        }
        return $highest ?? 0;
    }
}
