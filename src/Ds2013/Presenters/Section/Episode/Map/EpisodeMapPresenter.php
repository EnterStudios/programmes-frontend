<?php
declare(strict_types=1);

namespace App\Ds2013\Presenters\Section\Episode\Map;

use App\Ds2013\Presenter;
use App\Ds2013\Presenters\Section\Episode\Map\Panels\Main\DetailsPresenter;
use App\Ds2013\Presenters\Section\Episode\Map\Panels\Main\PlayoutPresenter;
use App\Ds2013\Presenters\Section\Episode\Map\Panels\Side\EmptyPresenter;
use App\Ds2013\Presenters\Section\Episode\Map\Panels\Side\MorePresenter;
use App\Ds2013\Presenters\Section\Episode\Map\Panels\Side\TxPresenter;
use App\DsShared\Helpers\PlayTranslationsHelper;
use App\DsShared\Helpers\LiveBroadcastHelper;
use App\DsShared\Helpers\StreamUrlHelper;
use BBC\ProgrammesPagesService\Domain\Entity\CollapsedBroadcast;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EpisodeMapPresenter extends Presenter
{
    /** @var CollapsedBroadcast|null */
    private $upcomingBroadcast;

    /** @var CollapsedBroadcast|null */
    private $lastOnBroadcast;

    /** @var Episode */
    private $episode;

    /** @var Presenter[] */
    private $sideSubPresenters;

    /** @var PlayoutPresenter */
    private $playoutSubpresenter;

    /** @var DetailsPresenter */
    private $detailsSubpresenter;

    /** @var Episode|null */
    private $nextEpisode;

    /** @var Episode|null */
    private $previousEpisode;

    public function __construct(
        UrlGeneratorInterface $router,
        LiveBroadcastHelper $liveBroadcastHelper,
        StreamUrlHelper $streamUrlHelper,
        PlayTranslationsHelper $playTranslationsHelper,
        Episode $episode,
        ?CollapsedBroadcast $upcoming,
        ?CollapsedBroadcast $lastOn,
        array $availableVersions,
        ?Episode $nextEpisode,
        ?Episode $previousEpisode
    ) {
        parent::__construct();
        $this->episode = $episode;
        $this->upcomingBroadcast = $upcoming;
        $this->lastOnBroadcast = $lastOn;
        $this->nextEpisode = $nextEpisode;
        $this->previousEpisode = $previousEpisode;
        $this->sideSubPresenters = $this->buildSidePanelsSubPresenters();
        $this->detailsSubpresenter = new DetailsPresenter($playTranslationsHelper, $router, $episode, $availableVersions);
        $this->playoutSubpresenter = new PlayoutPresenter($liveBroadcastHelper, $streamUrlHelper, $router, $episode, $upcoming, $lastOn, $availableVersions);
    }

    /**
     * @return array of Presenter
     */
    public function getSidePanelsSubPresenters() :array
    {
        return $this->sideSubPresenters;
    }

    public function getPlayoutSubpresenter() :PlayoutPresenter
    {
        return $this->playoutSubpresenter;
    }

    public function getDetailsSubpresenter() :DetailsPresenter
    {
        return $this->detailsSubpresenter;
    }

    /**
     * Show/hide panels
     */
    private function mustDisplayTxPanel() :bool
    {
        return !is_null($this->upcomingBroadcast) || !is_null($this->lastOnBroadcast);
    }

    private function buildSidePanelsSubPresenters() :array
    {
        $sidePanels = [];
        if ($this->mustDisplayTxPanel()) {
            $sidePanels[] = new TxPresenter($this->upcomingBroadcast, $this->lastOnBroadcast);
        }

        if (!$this->episode->isTleo()) {
            $sidePanels[] = new MorePresenter($this->episode, $this->nextEpisode, $this->previousEpisode);
        }

        if (empty($sidePanels) || $this->episode->isTleo()) {
            $sidePanels[] = new EmptyPresenter();
        }

        return $sidePanels;
    }
}
