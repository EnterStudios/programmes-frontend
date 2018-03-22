<?php
declare(strict_types=1);

namespace App\Ds2013\Presenters\Section\Segments;

use App\Ds2013\Presenter;
use App\Ds2013\Presenters\Section\Segments\Types\AbstractSegmentItemPresenter;
use App\Ds2013\Presenters\Section\Segments\Types\MusicSegmentItemPresenter;
use App\Ds2013\Presenters\Section\Segments\Types\SpeechSegmentItemPresenter;
use App\DsShared\Helpers\LiveBroadcastHelper;
use BBC\ProgrammesPagesService\Domain\ApplicationTime;
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
    private $programmeItem;

    /** @var CollapsedBroadcast|null */
    private $collapsedBroadcast;

    /** @var bool|null */
    private $isLive = null;

    public function __construct(
        LiveBroadcastHelper $liveBroadcastHelper,
        ProgrammeItem $programmeItem,
        array $segmentEvents,
        ?CollapsedBroadcast $upcoming,
        ?CollapsedBroadcast $lastOn,
        array $options = []
    ) {
        parent::__construct($options);
        $this->segmentEvents = $segmentEvents;
        $this->liveBroadcastHelper = $liveBroadcastHelper;
        $this->programmeItem = $programmeItem;
        $this->collapsedBroadcast = $upcoming ?? $lastOn;
        $this->segmentEvents = $this->filterSegmentEvents($programmeItem, $segmentEvents, $this->collapsedBroadcast);
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
            if (!$hasChapterSegments) {
                return 'music_played';
            }

            return 'music_and_featured';
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
        return $this->hasMoreless() ? 'ml' : '';
    }

    public function hasMoreless(): bool
    {
        return count($this->segmentEvents) >= 6;
    }

    private function filterSegmentEvents(
        ProgrammeItem $programmeItem,
        array $segmentEvents,
        ?CollapsedBroadcast $collapsedBroadcast
    ): array {
        if ($programmeItem->getOption('show_tracklist_inadvance')) {
            return $segmentEvents;
        }

        // if the programme item is currently being broadcast for the first time, filter to only the segment events that
        // have already started
        if ($collapsedBroadcast && !$collapsedBroadcast->isRepeat() && $this->isLive($collapsedBroadcast)) {
            $filteredSegmentEvents = [];
            $currentOffset = (ApplicationTime::getTime())->diff($collapsedBroadcast->getStartAt());

            $reverse = false;

            foreach ($segmentEvents as $segmentEvent) {
                if (!$reverse && $segmentEvent->getSegment() instanceof MusicSegment && $segmentEvent->getOffset()) {
                    $reverse = true;
                }

                if (!$segmentEvent->getOffset() || $currentOffset <= $segmentEvent->getOffset()) {
                    $segmentEvent[] = $segmentEvent;
                }
            }

            // music segments that have offsets get reversed
            if ($reverse) {
                $filteredSegmentEvents = array_reverse($filteredSegmentEvents);
            }

            return $filteredSegmentEvents;
        }

        return $segmentEvents;
    }

    public function getSegmentItemsPresenters(): array
    {
        $totalCount = count($this->segmentEvents);
        $groups = [];

        $group = [];
        $previousTitle = reset($this->segmentEvents)->getTitle();

        // We use the index and call it 'relative offset' here because we could
        // have reversed the array in case of music segments for live programme debuts.
        foreach ($this->segmentEvents as $relativeOffset => $segmentEvent) {
            // null, empty or different titles mean new group
            if (empty($previousTitle) || $segmentEvent->getTitle() != $previousTitle) {
                $groups[] = $group;
                $group = [];
            } else {
                $group[] = $this->createSegmentItem($segmentEvent, $relativeOffset,$totalCount);
            }

            $previousTitle = $segmentEvent->getTitle();
        }

        // add the last group that didn't get added in the loop (if it's not empty)
        if ($group) {
            $groups[] = $group;
        }

        return $groups;
    }

    private function createSegmentItem(
        SegmentEvent $segmentEvent,
        int $relativeOffset,
        int $totalCount
    ): AbstractSegmentItemPresenter {
        $options = [];

        // If there are more than 6 segments and we're past the third one, we start hiding things
        if ($relativeOffset > 3 && $totalCount >= 6) {
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
