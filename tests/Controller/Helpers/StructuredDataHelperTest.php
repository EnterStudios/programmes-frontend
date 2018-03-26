<?php
declare(strict_types = 1);

namespace Tests\App\Controller\Helpers;

use App\Builders\BroadcastBuilder;
use App\Builders\ClipBuilder;
use App\Builders\CollapsedBroadcastBuilder;
use App\Builders\EpisodeBuilder;
use App\Builders\MasterBrandBuilder;
use App\Builders\SeriesBuilder;
use App\Builders\ServiceBuilder;
use App\Controller\Helpers\SchemaHelper;
use App\Controller\Helpers\StructuredDataHelper;
use App\DsShared\Helpers\StreamUrlHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @group seo_schema
 */
class StructuredDataHelperTest extends TestCase
{
    /** @var StructuredDataHelper */
    private $helper;

    public function setUp()
    {
        $router = $this->createMock(UrlGeneratorInterface::class);
        $streamUrlHelper = $this->createMock(StreamUrlHelper::class);
        $this->helper = new StructuredDataHelper(new SchemaHelper($router, $streamUrlHelper));
    }

    /**
     *
     * [x] - Basic braodcasts and collapsedBroadcasts has correct info
     *
     */
    public function testOnDemandEpisodeIncludeCorrectFields()
    {
        $onDemandEpisode = EpisodeBuilder::any()->build();

        $ondemandSchema = $this->helper->getSchemaForOnDemand($onDemandEpisode);

        $this->assertKeys([
            '@type',
            'publishedOn',
            'duration',
            'url',
        ], $ondemandSchema);

        $this->assertEquals('OnDemandEvent', $ondemandSchema['@type']);
        $this->assertInternalType('array', $ondemandSchema['publishedOn']);
    }

    public function testPublishedOnContent()
    {
        $onDemandEpisode = EpisodeBuilder::any()->build();

        $publishedOnSchema = $this->helper->getSchemaForOnDemand($onDemandEpisode)['publishedOn'];

        $this->assertKeys([
            '@type',
            'broadcaster',
            'name',
        ], $publishedOnSchema);

        $this->assertEquals('BroadcastService', $publishedOnSchema['@type']);
        $this->assertInternalType('array', $publishedOnSchema['broadcaster']);
    }

    public function testBroadcasterHasTheRightContent()
    {
        $onDemandEpisode = EpisodeBuilder::any()->build();

        $broadcasterSchema = $this->helper->getSchemaForOnDemand($onDemandEpisode)['publishedOn']['broadcaster'];

        $this->assertKeys([
            '@type',
            'legalName',
            'logo',
            'name',
            'url',
        ], $broadcasterSchema);

        $this->assertEquals('Organization', $broadcasterSchema['@type']);
    }

    public function testBroadcastAddCorrectFields()
    {
        $broadcast = BroadcastBuilder::any()->build();

        $broadcastSchema = $this->helper->getSchemaForBroadcast($broadcast);

        $this->assertKeys([
            '@type',
            'startDate',
            'endDate',
            'publishedOn',
        ], $broadcastSchema);

        $this->assertEquals('BroadcastEvent', $broadcastSchema['@type']);
    }

    public function testServiceInPublicationHasCorrectInfo()
    {
        $broadcast = BroadcastBuilder::any()->build();

        $broadcastSchema = $this->helper->getSchemaForBroadcast($broadcast);

        $this->assertKeys([
            '@type',
            'broadcaster',
            'name',
        ], $broadcastSchema['publishedOn']);

        $this->assertEquals('BroadcastEvent', $broadcastSchema['@type']);
    }

    /**
     *
     * [x] - Schema understand the concept of multiple publications for collapsed broadcasts
     *
     */
    public function testCollapsedBroadcastUnderstandWhatIsAPublicationService()
    {
        $collapsedBroadcast = CollapsedBroadcastBuilder::any()->build();

        $collapsedSchema = $this->helper->getSchemaForCollapsedBroadcast($collapsedBroadcast);

        $this->assertKeys([
            '@type',
            'startDate',
            'endDate',
            'publishedOn',
        ], $collapsedSchema);

        $this->assertEquals('BroadcastEvent', $collapsedSchema['@type']);

        $this->assertCount(1, $collapsedSchema['publishedOn']);
    }

    public function testCollapsedBroadcastUnderstandWhatAreMultiplePublicationsServices()
    {
        $collapsedBroadcast = CollapsedBroadcastBuilder::any()->with([
            'services' => [
                $service1 = ServiceBuilder::any()->build(),
                $service2 = ServiceBuilder::any()->build(),
            ],
        ])->build();

        $publishedOnSchema = $this->helper->getSchemaForCollapsedBroadcast($collapsedBroadcast)['publishedOn'];

        $this->assertCount(2, $publishedOnSchema);
    }

    /**
     *
     * [x] - Understand the concept of tlec
     *
     */
    public function testUnderstandTheConceptOfTlec()
    {
        $seriesGrandPa = SeriesBuilder::any()->build();
        $seriesFather = SeriesBuilder::any()->with(['parent' => $seriesGrandPa])->build();
        $series = SeriesBuilder::any()->with(['parent' => $seriesFather])->build();

        $this->assertTrue($seriesGrandPa->isTlec());
        $this->assertFalse($seriesFather->isTlec());
        $this->assertFalse($series->isTlec());
    }

    public function testTlecProgrammesHasRightInformation()
    {
        $series = SeriesBuilder::any()->with([
            'masterBrand' => MasterBrandBuilder::anyRadioMasterBrand()->build(),
        ])->build();

        $containerSchema = $this->helper->getSchemaForProgrammeContainer($series);

        $this->assertKeys([
            '@type',
            'image',
            'description',
            'identifier',
            'name',
            'url',
        ], $containerSchema);

        $this->assertEquals('RadioSeries', $containerSchema['@type']);
    }

    /**
     *
     * [x] - Schema for episode know when is part of a series or a season
     *
     */
    public function testEpisodeWithTlecParentIsConsideredPartOfSeries()
    {
        $tlecSeriesFather = SeriesBuilder::any()->build();
        $nestedEpisode = EpisodeBuilder::any()->with(['parent' => $tlecSeriesFather])->build();

        $episodeSchema = $this->helper->getSchemaForEpisode($nestedEpisode, true);

        $this->assertEquals('TVEpisode', $episodeSchema['@type']);
        $this->assertArrayHasKey('partOfSeries', $episodeSchema);
        $this->assertArrayNotHasKey('partOfSeason', $episodeSchema);
    }

    public function testEpisodeWithNoTlecParentConsiderSeriesAndSeason()
    {
        $nestedEpisode = $this->getEpisodeNestedInMultipleSeries();

        $episodeSchema = $this->helper->getSchemaForEpisode($nestedEpisode, true);

        $this->assertArrayHasKey('partOfSeries', $episodeSchema);
        $this->assertArrayHasKey('partOfSeason', $episodeSchema);

        $this->assertKeys([
            '@type',
            'image',
            'description',
            'identifier',
            'name',
            'url',
        ], $episodeSchema['partOfSeries']);

        $this->assertKeys([
            '@type',
            'position',
            'name',
            'url',
        ], $episodeSchema['partOfSeason']);
    }

    public function testEpisodeSchemaCanAvoidToInjectParentsInformationIfIndicated()
    {
        $nestedEpisode = $this->getEpisodeNestedInMultipleSeries();

        $episodeSchema = $this->helper->getSchemaForEpisode($nestedEpisode, false);

        $this->assertArrayNotHasKey('partOfSeries', $episodeSchema);
        $this->assertArrayNotHasKey('partOfSeason', $episodeSchema);
    }

    /**
     *
     * [x] - Context work as expected adding indicated keys and wrapping content with @context
     *
     */
    public function testContextAddCorrectContent()
    {
        $schema = $this->helper->prepare([['foo' => 'bar'], ['baz' => 'qux']], true);

        $this->assertEquals([
            '@context' => 'http://schema.org',
            '@graph' => [
                ['foo' => 'bar'],
                ['baz' => 'qux'],
            ],
        ], $schema);
    }

    /**
     * [x] - Clips are generated
     */
    public function testCanCreateClipsSchemas()
    {
        $clip = ClipBuilder::anyRadioClip()->with([
            'parent' => SeriesBuilder::any()->build(),
        ])->build();

        $schemaClips = $this->helper->buildSchemaForClip($clip);

        $this->assertEquals('RadioClip', $schemaClips['@type'], 'We built a radio clip, so the schema:@type should be RadioClip');
    }

    private function getEpisodeNestedInMultipleSeries()
    {
        $tlecSeriesGrandpa = SeriesBuilder::any()->build();
        $seriesFather = SeriesBuilder::any()->with(['parent' => $tlecSeriesGrandpa])->build();
        $episode = EpisodeBuilder::any()->with(['parent' => $seriesFather])->build();

        return $episode;
    }

    private function assertKeys(array $expectedKeys, array $schema)
    {
        foreach ($expectedKeys as $expectedKey) {
            $this->assertArrayHasKey($expectedKey, $schema);
        }
    }
}
