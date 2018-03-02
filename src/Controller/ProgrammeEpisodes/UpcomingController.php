<?php
declare(strict_types = 1);

namespace App\Controller\ProgrammeEpisodes;

use App\Controller\Helpers\StructuredDataHelper;
use App\Ds2013\PresenterFactory;
use BBC\ProgrammesPagesService\Domain\Entity\CollapsedBroadcast;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeContainer;
use BBC\ProgrammesPagesService\Service\CollapsedBroadcastsService;
use BBC\ProgrammesPagesService\Service\ProgrammesAggregationService;

class UpcomingController extends BaseProgrammeEpisodesController
{
    public function __invoke(
        ProgrammeContainer $programme,
        ?string $debut,
        CollapsedBroadcastsService $collapsedBroadcastsService,
        PresenterFactory $presenterFactory,
        ProgrammesAggregationService $programmeAggregationService,
        StructuredDataHelper $structuredDataHelper
    ) {
        $this->setContextAndPreloadBranding($programme);
        $this->setIstatsProgsPageType('broadcast_slice');
        $this->setInternationalStatusAndTimezoneFromContext($programme);

        $subNavPresenter = $this->getSubNavPresenter($collapsedBroadcastsService, $programme, $presenterFactory);
        $upcomingBroadcasts = $collapsedBroadcastsService->findUpcomingByProgrammeWithFullServicesOfNetworksList($programme, 100);

        // display only debut broadcast if $debut is set
        if ($debut) {
            foreach ($upcomingBroadcasts as $key => $upcoming) {
                if ($upcoming->isRepeat()) {
                    unset($upcomingBroadcasts[$key]);
                }
            }
        }

        $schema = $this->getSchema($structuredDataHelper, $programme, $upcomingBroadcasts);

        return $this->renderWithChrome('programme_episodes/upcoming.html.twig', [
            'programme' => $programme,
            'upcomingBroadcasts' => $upcomingBroadcasts,
            'subNavPresenter' => $subNavPresenter,
            'debut' => $debut,
            'schema' => $schema,
        ]);
    }

    /**
     * @param StructuredDataHelper $structuredDataHelper
     * @param ProgrammeContainer $programmeContainer
     * @param CollapsedBroadcast[] $upcomingBroadcasts
     * @return array
     */
    private function getSchema(StructuredDataHelper $structuredDataHelper, ProgrammeContainer $programmeContainer, array $upcomingBroadcasts): array
    {
        $schemaContext = $this->getSchemaForProgrammeContainerAndParents($structuredDataHelper, $programmeContainer);

        foreach ($upcomingBroadcasts as $upcomingBroadcast) {
            $episodeSchema = $structuredDataHelper->getSchemaForEpisode($upcomingBroadcast->getProgrammeItem(), false);
            $episodeSchema['publication'] = $structuredDataHelper->getSchemaForCollapsedBroadcast($upcomingBroadcast);
            $schemaContext['episode'][] = $episodeSchema;
        }

        return $structuredDataHelper->prepare($schemaContext);
    }
}
