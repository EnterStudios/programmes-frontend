<?php
declare(strict_types=1);

namespace App\Ds2013\Presenters\Section\Segments;

use App\Ds2013\Presenter;
use App\Ds2013\Presenters\Section\Segments\Types\AbstractSegmentItemPresenter;
use App\Ds2013\Presenters\Section\Segments\Types\MusicSegmentItemPresenter;
use App\Ds2013\Presenters\Section\Segments\Types\SpeechSegmentItemPresenter;
use App\DsShared\Helpers\LiveBroadcastHelper;
use BBC\ProgrammesPagesService\Domain\ApplicationTime;
use BBC\ProgrammesPagesService\Domain\Entity\Clip;
use BBC\ProgrammesPagesService\Domain\Entity\CollapsedBroadcast;
use BBC\ProgrammesPagesService\Domain\Entity\MusicSegment;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeItem;
use BBC\ProgrammesPagesService\Domain\Entity\SegmentEvent;

class SegmentsListPresenter extends Presenter
{
    /** @var SegmentEvent[] */
    private $segmentEvents;

    /** @var LiveBroadcastHelper */
    private $liveBroadcastHelper;

    /** @var ProgrammeItem */
    private $context;

    /** @var CollapsedBroadcast|null */
    private $collapsedBroadcast;

    /** @var bool|null */
    private $isLive = null;

    /**
     * Has the list of segment events been reversed?
     *
     * @var bool
     */
    private $isReversed = false;

    /**
     * @param LiveBroadcastHelper $liveBroadcastHelper
     * @param SegmentEvent[] $segmentEvents
     * @param CollapsedBroadcast|null $upcoming
     * @param CollapsedBroadcast|null $lastOn
     * @param array $options
     */
    public function __construct(
        LiveBroadcastHelper $liveBroadcastHelper,
        ProgrammeItem $context,
        array $segmentEvents,
        ?CollapsedBroadcast $upcoming,
        ?CollapsedBroadcast $lastOn,
        array $options = []
    ) {
        parent::__construct($options);
        $this->liveBroadcastHelper = $liveBroadcastHelper;
        $this->context = $context;
        $this->collapsedBroadcast = $upcoming ?? $lastOn;
        $this->segmentEvents = $segmentEvents;
    }

    public function getTitle(): string
    {
        $hasChapterSegments = false;
        $hasMusicSegments = false;

        foreach ($this->segmentEvents as $segmentEvent) {
            if ($segmentEvent->getSegment() instanceof MusicSegment) {
                $hasMusicSegments = true;
            } elseif ($segmentEvent->getSegment()->getType() === 'chapter') {
                $hasChapterSegments = true;
            }
        }

        if ($hasMusicSegments) {
            if ($hasChapterSegments) {
                return 'music_and_featured';
            }

            return 'music_played';
        }

        if ($hasChapterSegments) {
            return 'chapters';
        }

        return 'featured';
    }

    public function getSegmentEvents(): array
    {
        return $this->segmentEvents;
    }

    public function getMorelessClass(): string
    {
        // radio only hides segments on < 600px
        if ($this->hasMoreless()) {
            if ($this->context->isRadio()) {
                return 'ml@bpb1';
            }

            return 'ml';
        }

        return '';
    }

    public function hasMoreless(): bool
    {
        return count($this->segmentEvents) >= 6;
    }

    public function hasTimingIntro(): bool
    {
        return !($this->context instanceof Clip) && $this->context->getOption('show_tracklist_timings');
    }

    public function getTimingIntroTranslationString(): string
    {
        if ($this->collapsedBroadcast && $this->collapsedBroadcast->getStartAt()->isFuture()) {
            return 'timings_start_of_day';
        }

        return 'timings_start_of_programme';
    }

    public function getSegmentItemsPresenters(): array
    {
        $segmentEvents = $this->filterSegmentEvents();

        $groups = [];
        $totalCount = count($this->segmentEvents);

        $group = [];
        $previousTitle = reset($this->segmentEvents)->getTitle();

        // We use the index and call it 'relative offset' here because we could
        // have reversed the array in case of music segments for live programme debuts.
        foreach ($segmentEvents as $relativeOffset => $segmentEvent) {
            // null, empty or different titles mean new group
            if (empty($previousTitle) || $segmentEvent->getTitle() != $previousTitle) {
                if ($group) {
                    $groups[] = $group;
                }

                $group = [];
            }

            $group[] = $this->createSegmentItem($segmentEvent, $relativeOffset, $totalCount);

            $previousTitle = $segmentEvent->getTitle();
        }

        // add the last group that didn't get added in the loop (if it's not empty)
        if ($group) {
            $groups[] = $group;
        }

        return $groups;
    }

    private function filterSegmentEvents(): array
    {
        if ($this->context->getOption('show_tracklist_inadvance')) {
            return $this->segmentEvents;
        }

        // if the programme item is currently being broadcast for the first time, filter to only the segment events that
        // have already started
        if ($this->collapsedBroadcast && !$this->collapsedBroadcast->isRepeat() && $this->isLive($this->collapsedBroadcast)) {
            $filteredSegmentEvents = [];
            $currentOffset = (ApplicationTime::getTime())->diff($this->collapsedBroadcast->getStartAt());

            foreach ($this->segmentEvents as $segmentEvent) {
                // music segments that have offsets get reversed
                // check if we're already reversing to avoid checking all conditions again
                if (!$this->isReversed && $segmentEvent->getSegment() instanceof MusicSegment && $segmentEvent->getOffset()) {
                    $this->isReversed = true;
                }

                // things within the offset range, or that don't have offsets get displayed
                if (!$segmentEvent->getOffset() || $currentOffset <= $segmentEvent->getOffset()) {
                    $filteredSegmentEvents[] = $segmentEvent;
                }
            }

            if ($this->isReversed) {
                $filteredSegmentEvents = array_reverse($filteredSegmentEvents);
            }

            return $filteredSegmentEvents;
        }

        return $this->segmentEvents;
    }

    private function createSegmentItem(
        SegmentEvent $segmentEvent,
        int $relativeOffset,
        int $totalCount
    ): AbstractSegmentItemPresenter {
        $options = [];

        // If there are more than 6 segments and we're past the third one, we start hiding things
        if ($relativeOffset > 2 && $totalCount >= 6) {
            $options['moreless_class'] = 'ml__hidden';
        }

        if ($segmentEvent->getSegment() instanceof MusicSegment) {
            return new MusicSegmentItemPresenter($options);
        }

        return new SpeechSegmentItemPresenter($options);
    }

    private function isLive(?CollapsedBroadcast $collapsedBroadcast): bool
    {
        if (is_null($this->isLive)) {
            $this->isLive = $collapsedBroadcast && $this->liveBroadcastHelper->isWatchableLive($collapsedBroadcast);
        }

        return $this->isLive;
    }
}
