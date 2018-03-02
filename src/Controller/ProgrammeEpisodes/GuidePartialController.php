<?php
declare(strict_types = 1);
namespace App\Controller\ProgrammeEpisodes;

use App\Controller\BaseController;
use App\Controller\Traits\IndexerTrait;
use App\Ds2013\PresenterFactory;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeContainer;
use BBC\ProgrammesPagesService\Service\CollapsedBroadcastsService;
use BBC\ProgrammesPagesService\Service\ProgrammesService;
use Symfony\Component\HttpFoundation\Request;

class GuidePartialController extends BaseController
{
    use IndexerTrait;

    public function __invoke(
        ProgrammeContainer $programme,
        PresenterFactory $presenterFactory,
        ProgrammesService $programmesService,
        CollapsedBroadcastsService $collapsedBroadcastService,
        Request $request
    ) {
        $nestedLevel = $request->query->get('nestedlevel');

        $this->setContextAndPreloadBranding($programme);
        $this->setIstatsProgsPageType('episodes_guide');

        $children = $programmesService->findEpisodeGuideChildren($programme, GuideController::LIMIT);
        if (!$children) {
            throw $this->createNotFoundException('Page does not exist');
        }

        $totalChildrenCount = $programmesService->countEpisodeGuideChildren($programme);
        $upcomingBroadcasts = $this->getUpcomingBroadcastsIndexedByProgrammePid($programme, $collapsedBroadcastService);

        return $this->renderWithChrome('programme_episodes/guide.2013inc.html.twig', [
            'programme' => $programme,
            'children' => $children,
            'totalChildrenCount' => $totalChildrenCount,
            'upcomingBroadcasts' => $upcomingBroadcasts,
            'nestedlevel' => (is_null($nestedLevel)) ? 1 : (int) $nestedLevel,
        ]);
    }
}
