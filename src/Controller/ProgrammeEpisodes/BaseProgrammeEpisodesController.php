<?php
declare(strict_types = 1);
namespace App\Controller\ProgrammeEpisodes;

use App\Controller\BaseController;
use App\Controller\Helpers\StructuredDataHelper;
use App\Ds2013\PresenterFactory;
use App\Ds2013\Presenters\Section\EpisodesSubNav\EpisodesSubNavPresenter;
use BBC\ProgrammesCachingLibrary\CacheInterface;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeContainer;
use BBC\ProgrammesPagesService\Service\CollapsedBroadcastsService;

abstract class BaseProgrammeEpisodesController extends BaseController
{
    protected function getSubNavPresenter(
        CollapsedBroadcastsService $collapsedBroadcastsService,
        ProgrammeContainer $programme,
        PresenterFactory $presenterFactory
    ): EpisodesSubNavPresenter {
        $upcomingBroadcastsCount = $collapsedBroadcastsService->countUpcomingByProgramme($programme, CacheInterface::MEDIUM);

        return $presenterFactory->episodesSubNavPresenter(
            $this->request()->attributes->get('_route'),
            $programme->getNetwork() === null || !$programme->getNetwork()->isInternational(),
            $programme->getFirstBroadcastDate() !== null,
            $programme->getAvailableEpisodesCount(),
            $programme->getPid(),
            $upcomingBroadcastsCount
        );
    }

    protected function getSchemaForProgrammeContainerAndParents(StructuredDataHelper $structuredDataHelper, ProgrammeContainer $programmeContainer): array
    {
        $schemaContext = $structuredDataHelper->getSchemaForProgrammeContainer($programmeContainer);

        if ($programmeContainer->isTlec()) {
            return $schemaContext;
        }

        $ancestry = \array_slice($programmeContainer->getAncestry(), 1); // First item is the programme itself, we only want the parents
        $tleo = array_pop($ancestry); // last item is the TLEO (pop removes this from the ancestry array too)

        foreach ($ancestry as $ancestor) {
            $schemaContext['partOfSeason'] = $structuredDataHelper->getSchemaForProgrammeContainer($ancestor);
        }
        $schemaContext['partOfSeries'] = $structuredDataHelper->getSchemaForProgrammeContainer($tleo);

        return $schemaContext;
    }
}
