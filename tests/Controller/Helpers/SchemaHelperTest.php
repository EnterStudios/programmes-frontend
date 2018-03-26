<?php
declare(strict_types = 1);

namespace Tests\App\Controller\Helpers;

use App\Builders\ClipBuilder;
use App\Builders\CollapsedBroadcastBuilder;
use App\Builders\EpisodeBuilder;
use App\Builders\ImageBuilder;
use App\Builders\SeriesBuilder;
use App\Builders\ServiceBuilder;
use App\Controller\Helpers\SchemaHelper;
use App\DsShared\Helpers\StreamUrlHelper;
use BBC\ProgrammesPagesService\Domain\ValueObject\PartialDate;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use BBC\ProgrammesPagesService\Domain\ValueObject\Synopses;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @group seo_schema
 */
class SchemaHelperTest extends TestCase
{
    /** @var SchemaHelper */
    private $helper;

    public function setUp()
    {
        $router = $this->createConfiguredMock(UrlGeneratorInterface::class, [
            'generate' => 'this/url/was/stubbed',
        ]);
        $this->helper = new SchemaHelper($router, $this->createMock(StreamUrlHelper::class));
    }

    public function testSchemaSeriesOutput()
    {
        $series = SeriesBuilder::anyRadioSeries()->with([
            'pid' => new Pid('b0000002'),
            'title' => 'some name',
            'synopses' => new Synopses('short', 'medium', 'long'),
            'image' => ImageBuilder::any()->with(['pid' => new Pid('b0000003')])->build(),
        ])->build();

        $schema = $this->helper->getSchemaForSeries($series);


        $this->assertEquals([
            '@type' => 'RadioSeries',
            'image' => 'https://ichef.bbci.co.uk/images/ic/480xn/b0000003.jpg',
            'description' => 'short',
            'identifier' => 'b0000002',
            'name' => 'some name',
            'url' => 'this/url/was/stubbed',
        ], $schema);
    }

    public function testGetSchemaForEpisode()
    {
        $episode = EpisodeBuilder::anyRadioEpisode()->with([
            'pid' => new Pid('b0000002'),
            'title' => 'some name',
            'image' => ImageBuilder::any()->with(['pid' => new Pid('b0000003')])->build(),
            'position' => 12,
            'releaseDate' => new PartialDate(3000, 04, 15),
            'synopses' => new Synopses('short', 'medium', 'long'),
        ])->build();

        $schema = $this->helper->getSchemaForEpisode($episode);

        $this->assertEquals([
            '@type' => 'RadioEpisode',
            'identifier' => 'b0000002',
            'episodeNumber' => 12,
            'description' => 'short',
            'datePublished' => '3000-04-15',
            'image' => 'https://ichef.bbci.co.uk/images/ic/480xn/b0000003.jpg',
            'name' => 'some name',
            'url' => 'this/url/was/stubbed',
        ], $schema);
    }

    public function testGetSchemaForOnDemandEventOutput()
    {
        $episode = EpisodeBuilder::anyRadioEpisode()->with([
            'streamableFrom' => new DateTimeImmutable('4000-02-03'),
            'streamableUntil' => new DateTimeImmutable('5000-02-03'),
        ])->build();

        $schema = $this->helper->getSchemaForOnDemandEvent($episode);

        $this->assertEquals([
            '@type' => 'OnDemandEvent',
            'url' => 'this/url/was/stubbed',
            'publishedOn' => [
                '@type' => 'BroadcastService',
                'broadcaster' => [
                    '@type' => 'Organization',
                    'legalName' => 'British Broadcasting Corporation',
                    'logo' => 'http://ichef.bbci.co.uk/images/ic/1200x675/p01tqv8z.png',
                    'name' => 'BBC',
                    'url' => 'https://www.bbc.co.uk/',
                ],
                'name' => 'iPlayer',
            ],
            'duration' => 'PT' . $episode->getDuration() . 'S',
            'startDate' => '4000-02-03T00:00:00+00:00',
            'endDate' => '5000-02-03T00:00:00+00:00',
        ], $schema);
    }

    public function testGetSchemaForBroadcastEventOutput()
    {
        $broadcast = CollapsedBroadcastBuilder::any()->build();

        $schema = $this->helper->getSchemaForBroadcastEvent($broadcast);

        $this->assertEquals([
            '@type' => 'BroadcastEvent',
            'startDate' => $broadcast->getStartAt()->format(DATE_ATOM),
            'endDate' => $broadcast->getEndAt()->format(DATE_ATOM),
        ], $schema);
    }

    public function testGetSchemaForServiceOutput()
    {
        $service = ServiceBuilder::any()->build();

        $schema = $this->helper->getSchemaForService($service);

        $this->assertEquals([
            '@type' => 'BroadcastService',
            'broadcaster' => [
                '@type' => 'Organization',
                'legalName' => 'British Broadcasting Corporation',
                'logo' => 'http://ichef.bbci.co.uk/images/ic/1200x675/p01tqv8z.png',
                'name' => 'BBC',
                'url' => 'https://www.bbc.co.uk/',
            ],
            'name' => $service->getName(),
        ], $schema);
    }

    public function testGetSchemaForSeason()
    {
        $series = SeriesBuilder::anyRadioSeries()->with(['pid' => new Pid('b0000002')])->build();
        $schema = $this->helper->getSchemaForSeason($series);

        $this->assertEquals([
            '@type' => 'RadioSeason',
            'identifier' => 'b0000002',
            'position' => $series->getPosition(),
            'name' => $series->getTitle(),
            'url' => 'this/url/was/stubbed',
        ], $schema);
    }

    /**
     *
     * [x] - Test communication between our clip schema generator and the router framework
     *
     */
    public function testTheUrlTryToGenerateTheUrlOfClipUsingThePidOFTheCLip()
    {
        $streamUrlHelper = $this->createMock(StreamUrlHelper::class);
        $routerMock = $this->createMock(UrlGeneratorInterface::class);
        $routerMock->expects($this->once())->method('generate')->with('find_by_pid', ['pid' => new Pid('b00819mm')]);

        $clip = ClipBuilder::anyRadioClip()->with(['pid' => new Pid('b00819mm')])->build();

        (new SchemaHelper($routerMock, $streamUrlHelper))->buildSchemaForClip($clip);
    }

    /**
     *
     * [x] - Clips: Has the right values and keys for all fields
     *
     */
    public function testValuesInClipSchemaAreCorrect()
    {
        $clip = ClipBuilder::anyRadioClip()->with([
            'parent' => SeriesBuilder::any()->build(),
            'pid' => new Pid('b00818rm'),
            'title' => 'a clip title',
            'releaseDate' => new PartialDate(2030, 02, 02),
            'synopses' => new Synopses('short', 'medium', 'long'),
            'image' => ImageBuilder::anyWithPid('b00819mm')->build(),
        ])->build();

        $schema = $this->helper->buildSchemaForClip($clip);

        $this->assertEquals([
            '@type' => 'RadioClip',
            'identifier' => 'b00818rm',
            'name' => 'a clip title',
            'datePublished' => '2030-02-02',
            'url' => 'this/url/was/stubbed',
            'image' => 'https://ichef.bbci.co.uk/images/ic/480xn/b00819mm.jpg',
            'description' => 'short',
        ], $schema);
    }

    /**
     *
     * [x] - Clips: Some fields might not appear when not configured
     *
     */
    public function testOptionalValuesNotAppearWhenNotConfigured()
    {
        $clip = ClipBuilder::anyRadioClip()->with([
            'parent' => SeriesBuilder::any()->build(),
        ])->build();

        $schema = $this->helper->buildSchemaForClip($clip);

        $this->assertArrayNotHasKey('releaseDate', $schema, 'releaseDate is an optional field: cannot exist if the clip has not a releaseDate');
    }
}
