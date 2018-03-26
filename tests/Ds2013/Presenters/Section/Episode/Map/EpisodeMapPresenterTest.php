<?php
declare(strict_types = 1);

namespace Tests\App\Ds2013\Presenters\Section\Episode\Map;

use App\Builders\CollapsedBroadcastBuilder;
use App\Builders\EpisodeBuilder;
use App\Builders\SeriesBuilder;
use App\Ds2013\Presenters\Section\Episode\Map\EpisodeMapPresenter;
use App\Ds2013\Presenters\Section\Episode\Map\Panels\Main\AbstractMainPanelMap;
use App\Ds2013\Presenters\Section\Episode\Map\Panels\Main\DetailsPresenter;
use App\Ds2013\Presenters\Section\Episode\Map\Panels\Main\PlayoutPresenter;
use App\Ds2013\Presenters\Section\Episode\Map\Panels\Side\AbstractSidePanelMap;
use App\DsShared\Helpers\LiveBroadcastHelper;
use App\DsShared\Helpers\PlayTranslationsHelper;
use App\Ds2013\Presenters\Section\Episode\Map\Panels\Side\EmptyPresenter;
use App\Ds2013\Presenters\Section\Episode\Map\Panels\Side\MorePresenter;
use App\Ds2013\Presenters\Section\Episode\Map\Panels\Side\TxPresenter;
use App\DsShared\Helpers\StreamUrlHelper;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouteCollectionBuilder;

/**
 * @group MapEpisode
 */
class EpisodeMapPresenterTest extends TestCase
{
    /**
     * [SIDE-TX PANEL]. Test presenter SHOW/HIDE
     *
     * broadcasts OR nextOn       - displayed
     * no broadcasts && no nextOn - no
     */
    public function testWhenBroadcastsExistThenTxPanelMustBeDisplayed()
    {
        $includeTxPanel = true;
        $includeMorePanel = false;
        $episodeMapPresenter = $this->getPresenterWithPanels($includeTxPanel, $includeMorePanel);

        $this->assertCount(2, $sidePanels = $episodeMapPresenter->getSidePanelsSubPresenters());
        $this->assertInstanceOf(TxPresenter::class, $txPanel = $sidePanels[0]);
    }

    /**
     * [SIDE-MORE PANEL]. Test presenter SHOW/HIDE
     *
     * no tleo programme - displayed
     * tleo programme    - no
     */
    public function testWhenProgrammeIsNotTleoThenMorePanelIsDisplayed()
    {
        $includeTxPanel = false;
        $includeMorePanel = true;
        $episodeMapPresenter = $this->getPresenterWithPanels($includeTxPanel, $includeMorePanel);

        $this->assertCount(1, $sidePanels = $episodeMapPresenter->getSidePanelsSubPresenters());
        $this->assertInstanceOf(MorePresenter::class, $morePanel = $sidePanels[0]);
    }

    /**
     * [SIDE-EMPTY PANEL]. Test presenter SHOW/HIDE
     *
     * NO broadcasts (no txpanel) || tleo programme (no morepanel) - displayed
     */
    public function testWhenThereIsNoPanelsThenEmptyPanelIsDisplayed()
    {
        $includeTxPanel = false;
        $includeMorePanel = false;
        $episodeMapPresenter = $this->getPresenterWithPanels($includeTxPanel, $includeMorePanel);

        $this->assertCount(1, $sidePanels = $episodeMapPresenter->getSidePanelsSubPresenters());
        $this->assertInstanceOf(EmptyPresenter::class, $emptyPanel = $sidePanels[0]);
    }

    public function testWhenThereIsNoMorePanelThenEmptyPanelIsDisplayed()
    {
        $includeTxPanel = true;
        $includeMorePanel = false;
        $episodeMapPresenter = $this->getPresenterWithPanels($includeTxPanel, $includeMorePanel);

        $this->assertCount(2, $sidePanels = $episodeMapPresenter->getSidePanelsSubPresenters());
        $this->assertInstanceOf(EmptyPresenter::class, $emptyPanel = $sidePanels[1]);
    }

    /**
     * [MAIN]. Test presenter know MAIN subpresenters
     */
    public function testGroupMainSubPresenters()
    {
        $episodeMapPresenter = $this->getPresenterWithPanels();

        $this->assertInstanceOf(PlayoutPresenter::class, $episodeMapPresenter->getPlayoutSubpresenter());
        $this->assertInstanceOf(DetailsPresenter::class, $episodeMapPresenter->getDetailsSubpresenter());
    }

    /**
     * SETUP PRESENTER
     */
    private function buildTleoProgramme() :Episode
    {
        return EpisodeBuilder::any()
            ->with(['parent' => null])
            ->build();
    }

    private function buildNoTleoProgramme() :Episode
    {
        return EpisodeBuilder::any()
             ->with(['parent' => SeriesBuilder::any()->build()])
             ->build();
    }

    private function getPresenterWithPanels($onlyTxPanel = false, $onlyMorePanel = false)
    {
        $playTranslationsHelper = $this->createMock(PlayTranslationsHelper::class);
        $liveBroadcastHelper = $this->createMock(LiveBroadcastHelper::class);
        $streamUrlHelper = $this->createMock(StreamUrlHelper::class);
        $router = $this->createMock(UrlGeneratorInterface::class);

        if (!$onlyMorePanel && !$onlyTxPanel) {
            $noTleoprogramme = $this->buildTleoProgramme();
            $upcomingBroadcast = null;
            $lastOnBroadcast = null;

            return new EpisodeMapPresenter($router, $liveBroadcastHelper, $streamUrlHelper, $playTranslationsHelper, $noTleoprogramme, $upcomingBroadcast, $lastOnBroadcast, [], null, null);
        }

        if ($onlyMorePanel && $onlyTxPanel) {
            $episode = $this->buildNoTleoProgramme();
            $upcomingBroadcast = CollapsedBroadcastBuilder::any()->with(['programmeItem' => $episode])->build();
            $lastOnBroadcast = CollapsedBroadcastBuilder::any()->with(['programmeItem' => $episode])->build();

            return new EpisodeMapPresenter($router, $liveBroadcastHelper, $streamUrlHelper, $playTranslationsHelper, $episode, $upcomingBroadcast, $lastOnBroadcast, [], null, null);
        }

        if ($onlyTxPanel) {
            $episode = $this->buildTleoProgramme();
            $upcomingBroadcast = CollapsedBroadcastBuilder::any()->with(['programmeItem' => $episode])->build();
            $lastOnBroadcast = CollapsedBroadcastBuilder::any()->with(['programmeItem' => $episode])->build();

            return new EpisodeMapPresenter($router, $liveBroadcastHelper, $streamUrlHelper, $playTranslationsHelper, $episode, $upcomingBroadcast, $lastOnBroadcast, [], null, null);
        }

        if ($onlyMorePanel) {
            $episode = $this->buildNoTleoProgramme();
            $upcomingBroadcast = null;
            $lastOnBroadcast = null;

            return new EpisodeMapPresenter($router, $liveBroadcastHelper, $streamUrlHelper, $playTranslationsHelper, $episode, $upcomingBroadcast, $lastOnBroadcast, [], null, null);
        }
    }
}
