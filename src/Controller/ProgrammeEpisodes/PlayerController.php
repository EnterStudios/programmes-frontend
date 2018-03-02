<?php
declare(strict_types = 1);

namespace App\Controller\ProgrammeEpisodes;

use App\Controller\Helpers\StructuredDataHelper;
use App\Ds2013\PresenterFactory;
use App\Ds2013\Presenters\Utilities\Paginator\PaginatorPresenter;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeContainer;
use BBC\ProgrammesPagesService\Service\CollapsedBroadcastsService;
use BBC\ProgrammesPagesService\Service\ProgrammesAggregationService;

class PlayerController extends BaseProgrammeEpisodesController
{
    public function __invoke(
        CollapsedBroadcastsService $collapsedBroadcastService,
        PresenterFactory $presenterFactory,
        ProgrammeContainer $programme,
        ProgrammesAggregationService $programmeAggregationService,
        StructuredDataHelper $structuredDataHelper
    ) {
        $this->setContextAndPreloadBranding($programme);
        $this->setIstatsProgsPageType('episodes_player');
        $page = $this->getPage();
        $limit = 10;

        $availableEpisodes = $programmeAggregationService->findStreamableOnDemandEpisodes(
            $programme,
            $limit,
            $page
        );

        // If you visit an out-of-bounds page then throw a 404. Page one should
        // always be a 200 so search engines don't drop their reference to the
        // page while a programme is off-air
        if (!$availableEpisodes && $page !== 1) {
            throw $this->createNotFoundException('Page does not exist');
        }

        $availableEpisodesCount = $programme->getAvailableEpisodesCount();

        $paginator = null;
        if ($availableEpisodesCount > $limit) {
            $paginator = new PaginatorPresenter($page, $limit, $availableEpisodesCount);
        }

        $this->setIstatsExtraLabels(
            [
                'has_available_items' => $availableEpisodesCount > 0 ? 'true' : 'false',
                'total_available_episodes' => (string) $availableEpisodesCount,
            ]
        );

        $subNavPresenter = $this->getSubNavPresenter($collapsedBroadcastService, $programme, $presenterFactory);
        $schema = $this->getSchema($structuredDataHelper, $programme, $availableEpisodes);

        return $this->renderWithChrome('programme_episodes/player.html.twig', [
            'programme' => $programme,
            'availableEpisodes' => $availableEpisodes,
            'paginatorPresenter' => $paginator,
            'subNavPresenter' => $subNavPresenter,
            'schema' => $schema,
        ]);
    }

    /**
     * @param StructuredDataHelper $structuredDataHelper
     * @param ProgrammeContainer $programmeContainer
     * @param Episode[] $availableEpisodes
     * @return array
     */
    private function getSchema(StructuredDataHelper $structuredDataHelper, ProgrammeContainer $programmeContainer, array $availableEpisodes): array
    {
        $schemaContext = $this->getSchemaForProgrammeContainerAndParents($structuredDataHelper, $programmeContainer);

        foreach ($availableEpisodes as $episode) {
            $episodeSchema = $structuredDataHelper->getSchemaForEpisode($episode, false);
            $episodeSchema['publication'] = $structuredDataHelper->getSchemaForOnDemand($episode);
            $schemaContext['episode'][] = $episodeSchema;
        }

        return $structuredDataHelper->prepare($schemaContext);
    }
}
