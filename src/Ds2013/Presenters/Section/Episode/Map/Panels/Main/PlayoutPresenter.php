<?php
declare(strict_types=1);
namespace App\Ds2013\Presenters\Section\Episode\Map\Panels\Main;

use App\Ds2013\Presenter;
use App\DsShared\Helpers\LiveBroadcastHelper;
use App\DsShared\Helpers\StreamUrlHelper;
use BBC\ProgrammesPagesService\Domain\Entity\CollapsedBroadcast;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use BBC\ProgrammesPagesService\Domain\Entity\Version;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PlayoutPresenter extends Presenter
{
    /** @var Episode */
    private $episode;

    /** @var CollapsedBroadcast|null */
    private $broadcast;

    /** @var LiveBroadcastHelper */
    private $liveBroadcastHelper;

    /** @var UrlGeneratorInterface */
    private $router;

    /** @var StreamUrlHelper */
    private $streamUrlHelper;

    /** @var Version[] */
    private $availableVersions;

    /** @var bool|null */
    private $isWatchableLive;

    public function __construct(
        LiveBroadcastHelper $liveBroadcastHelper,
        StreamUrlHelper $streamUrlHelper,
        UrlGeneratorInterface $router,
        Episode $episode,
        ?CollapsedBroadcast $upcoming,
        ?CollapsedBroadcast $lastOn,
        array $availableVersions
    ) {
        parent::__construct();
        $this->episode = $episode;
        $this->broadcast = $upcoming ?? $lastOn;
        $this->liveBroadcastHelper = $liveBroadcastHelper;
        $this->streamUrlHelper = $streamUrlHelper;
        $this->router = $router;
        $this->availableVersions = $availableVersions;
        $this->isWatchableLive = null;
    }

    public function getPanelId(): string
    {
        return 'episode-playout-' . $this->episode->getPid();
    }

    public function getEpisode(): Episode
    {
        return $this->episode;
    }

    public function doesntHaveOverlay(): bool
    {
        // It's a bit odd checking if something is not true, but the logic for deciding if an overlay exists
        // is more complicated than deciding if an overlay doesn't exist
        // If the episode is not available for streaming (either on demand or on simulcast), but it has any version
        // of the following types, or the episode belongs to an international network, we don't show an overlay

        // If the episode is available for streaming there's a CTA, so there's an overlay
        if ($this->isAvailableForStreaming()) {
            return false;
        }

        // International networks don't get overlays
        if ($this->episode->getNetwork() && $this->episode->getNetwork()->isInternational()) {
            return true;
        }

        // Downloadable episodes only get the download button, which is not an overlay
        if ($this->episode->isDownloadable()) {
            return true;
        }

        $relevantVersions = ['DubbedAudioDescribed', 'Signed'];

        foreach ($this->availableVersions as $version) {
            foreach ($version->getVersionTypes() as $type) {
                if (in_array($type->getType(), $relevantVersions)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getIcon(): string
    {
        return $this->episode->isRadio() ? 'iplayer-radio' : 'iplayer';
    }

    public function getAvailableTranslation(): string
    {
        if ($this->isWatchableLive()) {
            if ($this->episode->isAudio()) {
                return 'iplayer_listen_live';
            }

            return 'iplayer_watch_live';
        }

        if ($this->episode->isAudio()) {
            return 'iplayer_listen_now';
        }

        return 'iplayer_watch_now';
    }

    public function getNotAvailableTranslation(): string
    {
        if ($this->broadcast && $this->broadcast->getStartAt()->isFuture() &&
            ($this->episode->hasFutureAvailability() || $this->episode->isRadio())
        ) {
            return 'available_shortly';
        }

        if ($this->episode->hasFutureAvailability()) {
            return 'episode_availability_future';
        }

        $suffix = $this->episode->isRadio() ? '_radio' : '';
        $prefix = $this->episode->isTleo() ? 'programme_' : 'episode_';

        return $prefix . 'availability_none' . $suffix;
    }

    public function isAvailableForStreaming(): bool
    {
        return $this->episode->isStreamable() || $this->isWatchableLive();
    }

    public function getUrl(): string
    {
        if ($this->isWatchableLive()) {
            return $this->liveBroadcastHelper->simulcastUrl($this->broadcast);
        }

        return $this->router->generate($this->streamUrlHelper->getRouteForProgrammeItem($this->episode), ['pid' => (string) $this->episode->getPid()]);
    }

    private function isWatchableLive(): bool
    {
        if (is_null($this->isWatchableLive)) {
            $this->isWatchableLive =
                $this->broadcast ? $this->liveBroadcastHelper->isWatchableLive($this->broadcast, true) : false;
        }

        return $this->isWatchableLive;
    }
}
