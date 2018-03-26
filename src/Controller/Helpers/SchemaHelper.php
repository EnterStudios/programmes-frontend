<?php
declare(strict_types = 1);

namespace App\Controller\Helpers;

use App\DsShared\Helpers\StreamUrlHelper;
use BBC\ProgrammesPagesService\Domain\Entity\BroadcastInfoInterface;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use BBC\ProgrammesPagesService\Domain\Entity\Network;
use BBC\ProgrammesPagesService\Domain\Entity\Clip;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeContainer;
use BBC\ProgrammesPagesService\Domain\Entity\Series;
use BBC\ProgrammesPagesService\Domain\Entity\Service;
use Cake\Chronos\ChronosInterval;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Schema.org domain language
 * An Episode can belong to a Season and a Series
 * A Season can belong to Series
 * A Series is the top-level item
 */
class SchemaHelper
{
    /** @var UrlGeneratorInterface */
    private $router;

    /** @var StreamUrlHelper */
    private $streamUrlHelper;

    public function __construct(UrlGeneratorInterface $router, StreamUrlHelper $streamUrlHelper)
    {
        $this->router = $router;
        $this->streamUrlHelper = $streamUrlHelper;
    }

    public function getSchemaForSeries(ProgrammeContainer $programme): array
    {
        return [
            '@type' => $programme->isRadio() ? 'RadioSeries' : 'TVSeries',
            'image' => $programme->getImage()->getUrl(480),
            'description' => $programme->getShortSynopsis(),
            'identifier' => $programme->getPid(),
            'name' => $programme->getTitle(),
            'url' => $this->router->generate('find_by_pid', ['pid' => $programme->getPid()], UrlGeneratorInterface::ABSOLUTE_URL),
        ];
    }

    public function prepare($schemaToPrepare, $isArrayOfContexts = false): array
    {
        if ($isArrayOfContexts) {
            $schema = [
                '@context' => 'http://schema.org',
                '@graph' => $schemaToPrepare,
            ];
            return $schema;
        }

        $schemaToPrepare['@context'] = 'http://schema.org';

        return $schemaToPrepare;
    }

    public function getSchemaForEpisode(Episode $episode): array
    {
        return [
            '@type' => $episode->isRadio() ? 'RadioEpisode' : 'TVEpisode',
            'identifier' => $episode->getPid(),
            'episodeNumber' => $episode->getPosition(),
            'description' => $episode->getShortSynopsis(),
            'datePublished' => $episode->getReleaseDate(),
            'image' => $episode->getImage()->getUrl(480),
            'name' => $episode->getTitle(),
            'url' => $this->router->generate('find_by_pid', ['pid' => $episode->getPid()], UrlGeneratorInterface::ABSOLUTE_URL),
        ];
    }

    public function getSchemaForOnDemandEvent(Episode $episode): array
    {
        $event = [
            '@type' => 'OnDemandEvent',
            'publishedOn' => [
                '@type' => 'BroadcastService',
                'broadcaster' => $this->getSchemaForOrganisation(),
                'name' => 'iPlayer',
            ],
            'duration' => (string) new ChronosInterval(null, null, null, null, null, null, $episode->getDuration()),
            'url' => $this->router->generate($this->streamUrlHelper->getRouteForProgrammeItem($episode), ['pid' => $episode->getPid()], UrlGeneratorInterface::ABSOLUTE_URL),
        ];
        if ($episode->getStreamableFrom()) {
            $event['startDate'] = $episode->getStreamableFrom()->format(DATE_ATOM);
        }
        if ($episode->getStreamableUntil()) {
            $event['endDate'] = $episode->getStreamableUntil()->format(DATE_ATOM);
        }

        return $event;
    }

    public function getSchemaForOrganisation(): array
    {
        return [
            '@type' => 'Organization',
            'legalName' => 'British Broadcasting Corporation',
            'logo' => 'http://ichef.bbci.co.uk/images/ic/1200x675/p01tqv8z.png',
            'name' => 'BBC',
            'url' => 'https://www.bbc.co.uk/',
        ];
    }

    public function getSchemaForBroadcastEvent(BroadcastInfoInterface $broadcast): array
    {
        return [
            '@type' => 'BroadcastEvent',
            'startDate' => $broadcast->getStartAt()->format(DATE_ATOM),
            'endDate' => $broadcast->getEndAt()->format(DATE_ATOM),
        ];
    }

    /**
     * @param Service|Network $service
     * @return array
     */
    public function getSchemaForService($service): array
    {
        $bbcContext = $this->getSchemaForOrganisation();

        return [
            '@type' => 'BroadcastService',
            'broadcaster' => $bbcContext,
            'name' => $service->getName(),
        ];
    }

    public function getSchemaForSeason(Series $season): array
    {
        return [
            '@type' => $season->isRadio() ? 'RadioSeason' : 'TVSeason',
            'position' => $season->getPosition(),
            'identifier' => $season->getPid(),
            'name' => $season->getTitle(),
            'url' => $this->router->generate('find_by_pid', ['pid' => $season->getPid()], UrlGeneratorInterface::ABSOLUTE_URL),
        ];
    }

    public function buildSchemaForClip(Clip $clip) :array
    {
        $clipSchema = [];
        $clipSchema['@type'] = $clip->isRadio() ? 'RadioClip' : 'TVClip';
        $clipSchema['identifier'] = (string) $clip->getPid();
        $clipSchema['name'] = $clip->getTitle();

        if (!is_null($clip->getReleaseDate())) {
            $clipSchema['datePublished'] = (string) $clip->getReleaseDate();
        }

        $clipSchema['url'] = $this->router->generate('find_by_pid', ['pid' => $clip->getPid()], UrlGeneratorInterface::ABSOLUTE_URL);
        $clipSchema['image'] = $clip->getImage()->getUrl(480);
        $clipSchema['description'] = $clip->getShortSynopsis();

        return $clipSchema;
    }
}
