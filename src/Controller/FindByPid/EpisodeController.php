<?php
declare(strict_types = 1);
namespace App\Controller\FindByPid;

use App\Controller\BaseController;
use App\Ds2013\PresenterFactory;
use App\Ds2013\Presenters\Section\Segments\SegmentsPresenter;
use App\ExternalApi\FavouritesButton\Service\FavouritesButtonService;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use BBC\ProgrammesPagesService\Service\CollapsedBroadcastsService;
use BBC\ProgrammesPagesService\Service\ContributionsService;
use BBC\ProgrammesPagesService\Service\ProgrammesAggregationService;
use BBC\ProgrammesPagesService\Service\ProgrammesService;
use BBC\ProgrammesPagesService\Service\PromotionsService;
use BBC\ProgrammesPagesService\Service\RelatedLinksService;
use BBC\ProgrammesPagesService\Service\SegmentEventsService;
use BBC\ProgrammesPagesService\Service\VersionsService;
use GuzzleHttp\Promise\FulfilledPromise;

class EpisodeController extends BaseController
{
    public function __invoke(
        Episode $episode,
        ContributionsService $contributionsService,
        ProgrammesService $programmesService,
        ProgrammesAggregationService $aggregationService,
        PromotionsService $promotionsService,
        RelatedLinksService $relatedLinksService,
        CollapsedBroadcastsService $collapsedBroadcastsService,
        FavouritesButtonService $favouritesButtonService,
        VersionsService $versionsService,
        SegmentEventsService $segmentEventsService,
        PresenterFactory $presenterFactory
    ) {
        $segmentEvents = $segmentEventsService->findByProgrammeItemUsingOriginalVersion($episode);
        $segmentsPresenter = new SegmentsPresenter($segmentEvents);

        $this->setIstatsProgsPageType('programmes_episode');
        $this->setContextAndPreloadBranding($episode);

        $clips = [];
        if ($episode->getAvailableClipsCount() > 0) {
            $clips = $aggregationService->findStreamableDescendantClips($episode, 4);
        }

        $galleries = [];
        if ($episode->getAggregatedGalleriesCount() > 0) {
            $galleries = $aggregationService->findDescendantGalleries($episode, 4);
        }

        $contributions = [];
        if ($episode->getContributionsCount() > 0) {
            $contributions = $contributionsService->findByContributionToProgramme($episode);
        }

        $relatedLinks = [];
        if ($episode->getRelatedLinksCount() > 0) {
            $relatedLinks = $relatedLinksService->findByRelatedToProgramme($episode, ['related_site', 'miscellaneous']);
        }
        $upcomingBroadcasts = [];
        $lastOnBroadcasts = [];
        $allBroadcasts = [];
        if ($episode->getFirstBroadcastDate()) {
            // Only search for broadcasts if a programme has them
            $upcomingBroadcasts = $collapsedBroadcastsService->findUpcomingByProgrammeWithFullServicesOfNetworksList($episode, 1);
            $lastOnBroadcasts = $collapsedBroadcastsService->findPastByProgrammeWithFullServicesOfNetworksList($episode, 1);
            $allBroadcasts = $collapsedBroadcastsService->findByProgrammeWithFullServicesOfNetworksList($episode, 100);
        }

        $availableVersions = [];
        if ($episode->isStreamableAlternatate() || $episode->isDownloadable()) {
            $availableVersions = $versionsService->findAvailableByProgrammeItem($episode);
        }

        // TODO check $episode->getPromotionsCount() once it is populated in
        // Faucet to potentially save on a DB query
        $promotions = $promotionsService->findActivePromotionsByEntityGroupedByType($episode);

        /** @var Episode|null $nextEpisode */
        $nextEpisode = null;
        /** @var Episode|null $previousEpisode */
        $previousEpisode = null;
        if (!$episode->isTleo()) {
            $nextEpisode = $programmesService->findNextSiblingByProgramme($episode);
            $previousEpisode = $programmesService->findPreviousSiblingByProgramme($episode);
        }

        $episodeMapPresenter = $presenterFactory->episodeMapPresenter(
            $episode,
            $availableVersions,
            !empty($upcomingBroadcasts) ? reset($upcomingBroadcasts) : null,
            !empty($lastOnBroadcasts) ? reset($lastOnBroadcasts) : null,
            $nextEpisode,
            $previousEpisode
        );

        $favouritesButtonPromise = new FulfilledPromise(null);
        if ($episode->isRadio()) {
            $favouritesButtonPromise = $favouritesButtonService->getContent();
        }

        $resolvedPromises = $this->resolvePromises(['favouritesButton' => $favouritesButtonPromise]);

        return $this->renderWithChrome('find_by_pid/episode.html.twig', [
            'contributions' => $contributions,
            'programme' => $episode,
            'clips' => $clips,
            'galleries' => $galleries,
            'relatedLinks' => $relatedLinks,
            'promotions' => $promotions,
            'allBroadcasts' => $allBroadcasts,
            'episodeMapPresenter' => $episodeMapPresenter,
            'favouritesButton' => $resolvedPromises['favouritesButton'],
        ]);
    }
}
