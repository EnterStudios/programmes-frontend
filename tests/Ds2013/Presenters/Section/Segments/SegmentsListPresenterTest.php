<?php
declare(strict_types=1);

namespace Tests\App\Ds2013\Presenters\Section\Segments;

use App\Ds2013\Presenters\Section\Segments\SegmentsListPresenter;
use App\DsShared\Helpers\LiveBroadcastHelper;
use BBC\ProgrammesPagesService\Domain\Entity\Clip;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use BBC\ProgrammesPagesService\Domain\Entity\MusicSegment;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeItem;
use BBC\ProgrammesPagesService\Domain\Entity\Segment;
use BBC\ProgrammesPagesService\Domain\Entity\SegmentEvent;
use PHPUnit\Framework\TestCase;

class SegmentsListPresenterTest extends TestCase
{
    /** @var LiveBroadcastHelper */
    private $mockLiveBroadcastHelper;

    public function setUp()
    {
        $this->mockLiveBroadcastHelper = $this->createMock(LiveBroadcastHelper::class);
    }

    /** @dataProvider getTitleProvider */
    public function testGetTitle(string $expected, array $segmentEvents)
    {
        $episode = $this->createMock(Episode::class);
        $presenter = new SegmentsListPresenter($this->mockLiveBroadcastHelper, $episode, $segmentEvents, null, null, []);
        $this->assertEquals($expected, $presenter->getTitle());
    }

    public function getTitleProvider(): array
    {
        $musicSegment = $this->createMock(MusicSegment::class);
        $chapterSegment = $this->createConfiguredMock(Segment::class, ['getType' => 'chapter']);
        $highlightSegment = $this->createConfiguredMock(Segment::class, ['getType' => 'highlight']);
        $speechSegment = $this->createConfiguredMock(Segment::class, ['getType' => 'speech']);

        $musicSegmentEvent = $this->createConfiguredMock(SegmentEvent::class, ['getSegment' => $musicSegment]);
        $chapterSegmentEvent = $this->createConfiguredMock(SegmentEvent::class, ['getSegment' => $chapterSegment]);
        $highlightSegmentEvent = $this->createConfiguredMock(SegmentEvent::class, ['getSegment' => $highlightSegment]);
        $speechSegmentEvent = $this->createConfiguredMock(SegmentEvent::class, ['getSegment' => $speechSegment]);

        return [
            'only music segments' => ['music_played', [$musicSegmentEvent, $highlightSegmentEvent, $speechSegmentEvent]],
            'music and chapter segments' => ['music_and_featured', [$musicSegmentEvent, $chapterSegmentEvent]],
            'only chapter segments' => ['chapters', [$chapterSegmentEvent]],
            'other types of segments' => ['featured', [$highlightSegmentEvent, $speechSegmentEvent]],
            'chapter has preference over non-music segments' => ['chapters', [$chapterSegmentEvent, $highlightSegmentEvent, $speechSegmentEvent]],
            'music has preference over all types' => ['music_played', [$musicSegmentEvent, $highlightSegmentEvent, $speechSegmentEvent]],
            'music, chapter and others segments' => ['music_played', [$musicSegmentEvent, $highlightSegmentEvent, $speechSegmentEvent]],
        ];
    }

    /** @dataProvider getMorelessClassProvider */
    public function testGetMorelessClass(string $expected, array $segmentEvents, ProgrammeItem $context)
    {
        $presenter = new SegmentsListPresenter($this->mockLiveBroadcastHelper, $context, $segmentEvents, null, null, []);
        $this->assertEquals($expected, $presenter->getMorelessClass());
    }

    public function getMorelessClassProvider(): array
    {
        $seg1 = $this->createMock(SegmentEvent::class);
        $seg2 = $this->createMock(SegmentEvent::class);
        $seg3 = $this->createMock(SegmentEvent::class);
        $seg4 = $this->createMock(SegmentEvent::class);
        $seg5 = $this->createMock(SegmentEvent::class);
        $seg6 = $this->createMock(SegmentEvent::class);
        $seg7 = $this->createMock(SegmentEvent::class);

        $sixSegmentEvents = [$seg1, $seg2, $seg3, $seg4, $seg5, $seg6];
        $sevenSegmentEvents = [$seg1, $seg2, $seg3, $seg4, $seg5, $seg6, $seg7];
        $fiveSegmentEvents = [$seg1, $seg2, $seg3, $seg4, $seg5];

        $radioContext = $this->createConfiguredMock(ProgrammeItem::class, ['isRadio' => true]);
        $nonRadioContext = $this->createConfiguredMock(ProgrammeItem::class, ['isRadio' => false]);

        return [
            'radio context with more than 6 segments' => ['ml@bpb1', $sevenSegmentEvents, $radioContext],
            'non radio context with more than 6 segments' => ['ml', $sevenSegmentEvents, $nonRadioContext],
            'radio context with exactly 6 segments' => ['ml@bpb1', $sixSegmentEvents, $radioContext],
            'non radio context with exactly 6 segments' => ['ml', $sixSegmentEvents, $nonRadioContext],
            'radio context with less than 6 segments' => ['', $fiveSegmentEvents, $radioContext],
            'non radio context with less than 6 segments' => ['', $fiveSegmentEvents, $nonRadioContext],
        ];
    }

    /** @dataProvider hasTimingIntroProvider */
    public function testHasTimingIntro(bool $expected, ProgrammeItem $context)
    {
        $presenter = new SegmentsListPresenter($this->mockLiveBroadcastHelper, $context, [], null, null, []);
        $this->assertEquals($expected, $presenter->hasTimingIntro());
    }

    public function hasTimingIntroProvider(): array
    {
        $episodeWithTiming = $this->createMock(Episode::class);
        $episodeWithTiming->expects($this->once())->method('getOption')->with('show_tracklist_timings')->willReturn(true);

        $clipWithTiming = $this->createMock(Clip::class);
        $clipWithTiming->expects($this->never())->method('getOption');

        $episodeWithoutTiming = $this->createMock(Episode::class);
        $episodeWithoutTiming->expects($this->once())->method('getOption')->with('show_tracklist_timings')->willReturn(false);

        $clipWithoutTiming = $this->createMock(Clip::class);
        $clipWithoutTiming->expects($this->never())->method('getOption');

        return [
            'clip with show_tracklist_timings option' => [false, $clipWithTiming],
            'clip without show_tracklist_timings option' => [false, $clipWithoutTiming],
            'episode with show_tracklist_timings option' => [true, $episodeWithTiming],
            'episode without show_tracklist_timings option' => [false, $episodeWithoutTiming],
        ];
    }
}
