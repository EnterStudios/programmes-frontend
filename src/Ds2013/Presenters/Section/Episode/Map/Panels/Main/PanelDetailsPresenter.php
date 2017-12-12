<?php
declare(strict_types=1);
namespace App\Ds2013\Presenters\Section\Episode\Map\Panels\Main;

use App\Ds2013\Presenter;
use App\DsShared\Helpers\PlayTranslationsHelper;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use BBC\ProgrammesPagesService\Domain\Entity\Version;
use Cake\Chronos\Chronos;
use DateTime;

class PanelDetailsPresenter extends Presenter
{
    /** @var Episode */
    private $episode;

    /** @var PlayTranslationsHelper */
    private $playTranslationsHelper;

    /** @var Version[] */
    private $streamableVersions;

    public function __construct(PlayTranslationsHelper $playTranslationsHelper, Episode $episode, array $streamableVersions)
    {
        parent::__construct();

        $this->episode = $episode;
        $this->playTranslationsHelper = $playTranslationsHelper;
        $this->streamableVersions = $streamableVersions;
    }

    public function getEpisode(): Episode
    {
        return $this->episode;
    }

    public function hasPreviousBroadcast(): bool
    {
        return $this->episode->getFirstBroadcastDate() && $this->episode->getFirstBroadcastDate()->isPast();
    }

    public function getReleaseDate(): ?DateTime
    {
        if ($this->episode->getReleaseDate()) {
            return $this->episode->getReleaseDate()->asDateTime();
        }

        return null;
    }

    /**
     * @see https://confluence.dev.bbc.co.uk/display/programmes/Versions+and+Availability
     */
    public function isAvailableIndefinitely(): bool
    {
        if (!$this->episode->getStreamableUntil()) {
            return true;
        }

        return !$this->episode->getStreamableUntil()->isWithinNext('1 year');
    }

    public function getStreamableTimeRemaining(): string
    {
        $timeRemaining = $this->episode->getStreamableUntil()->diff(Chronos::now());

        $string = 'iplayer_play_remaining';
        if ($this->episode->getMediaType()) {
            if ($this->episode->getMediaType() === 'audio') {
                $string = 'iplayer_listen_remaining';
            } else {
                $string = 'iplayer_watch_remaining';
            }
        } elseif ($this->episode->isRadio()) {
            $string = 'iplayer_listen_remaining';
        } elseif ($this->episode->isTv()) {
            $string = 'iplayer_watch_remaining';
        }
        return $this->playTranslationsHelper->timeIntervalToWords($timeRemaining, false, $string);
    }

    public function getDuration(): string
    {
        return $this->playTranslationsHelper->secondsToWords($this->episode->getDuration());
    }

    public function hasAvailableAudioDescribedVersion(): bool
    {
        return $this->hasAvailableVersion('DubbedAudioDescribed');
    }

    public function hasAvailableSignedVersion(): bool
    {
        return $this->hasAvailableVersion('Signed');
    }

    private function hasAvailableVersion(string $versionType)
    {
        foreach ($this->streamableVersions as $version) {
            if ($version->isStreamable()) {
                foreach ($version->getVersionTypes() as $type) {
                    if ($type->getType() === $versionType) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}