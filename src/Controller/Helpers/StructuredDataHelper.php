<?php
declare(strict_types = 1);

namespace App\Controller\Helpers;

use BBC\ProgrammesPagesService\Domain\Entity\Broadcast;
use BBC\ProgrammesPagesService\Domain\Entity\Clip;
use BBC\ProgrammesPagesService\Domain\Entity\CollapsedBroadcast;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeContainer;
use BBC\ProgrammesPagesService\Domain\Entity\Series;
use BBC\ProgrammesPagesService\Domain\Entity\Service;

/**
 * Method names are BBC domain language
 * Methods call out to Schema.org domain language methods from SchemaHelper
 */
class StructuredDataHelper
{
    /** @var SchemaHelper */
    private $schemaHelper;

    public function __construct(SchemaHelper $schemaHelper)
    {
        $this->schemaHelper = $schemaHelper;
    }

    public function getSchemaForBroadcast(Broadcast $broadcast): array
    {
        $broadcastEvent = $this->schemaHelper->getSchemaForBroadcastEvent($broadcast);
        $broadcastEvent['publishedOn'] = $this->getSchemaForService($broadcast->getService());

        return $broadcastEvent;
    }

    public function getSchemaForCollapsedBroadcast(CollapsedBroadcast $collapsedBroadcast): array
    {
        $broadcastEvent = $this->schemaHelper->getSchemaForBroadcastEvent($collapsedBroadcast);

        $broadcastEvent['publishedOn'] = [];
        foreach ($collapsedBroadcast->getServices() as $service) {
            $broadcastEvent['publishedOn'][] = $this->getSchemaForService($service);
        }

        return $broadcastEvent;
    }

    public function getSchemaForOnDemand(Episode $episode): array
    {
        return $this->schemaHelper->getSchemaForOnDemandEvent($episode);
    }

    public function prepare($schemaToPrepare, $isArrayOfContexts = false): array
    {
        return $this->schemaHelper->prepare($schemaToPrepare, $isArrayOfContexts);
    }

    public function getSchemaForEpisode(Episode $programmeItem, bool $includeParent): array
    {
        $episode = $this->schemaHelper->getSchemaForEpisode($programmeItem);
        $parent = $programmeItem->getParent();
        if ($parent && $includeParent) {
            if ($parent->isTlec()) {
                $episode['partOfSeries'] = $this->schemaHelper->getSchemaForSeries($parent);
            } else {
                $episode['partOfSeries'] = $this->schemaHelper->getSchemaForSeries($parent->getTleo());
                $episode['partOfSeason'] = $this->schemaHelper->getSchemaForSeason($parent);
            }
        }
        return $episode;
    }

    public function getSchemaForProgrammeContainer(ProgrammeContainer $programmeContainer): array
    {
        if ($programmeContainer->isTlec()) {
            return $this->schemaHelper->getSchemaForSeries($programmeContainer);
        }

        /** @var Series $programmeContainer */
        return $this->schemaHelper->getSchemaForSeason($programmeContainer);
    }

    public function buildSchemaForClip(Clip $clip) :array
    {
        return $this->schemaHelper->buildSchemaForClip($clip);
    }

    private function getSchemaForService(Service $service): array
    {
        $serviceContext = $this->schemaHelper->getSchemaForService($service);

        $network = $service->getNetwork();
        if ($network !== null && $network->getName() !== $service->getName()) {
            $networkContext = $this->schemaHelper->getSchemaForService($network);
            $networkContext['logo'] =  $network->getImage()->getUrl(480);
            $serviceContext['parentService'] = $networkContext;
        }

        return $serviceContext;
    }
}
