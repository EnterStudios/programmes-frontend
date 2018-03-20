<?php
declare(strict_types=1);

namespace App\Ds2013\Presenters\Section\Segments;

use App\Ds2013\Presenter;
use App\Ds2013\Presenters\Section\Segments\Types\AbstractSegmentItem;
use App\Ds2013\Presenters\Section\Segments\Types\MusicSegmentItem;
use App\Ds2013\Presenters\Section\Segments\Types\SpeechSegmentItem;
use App\DsShared\Helpers\LiveBroadcastHelper;
use BBC\ProgrammesPagesService\Domain\ApplicationTime;
use BBC\ProgrammesPagesService\Domain\Entity\CollapsedBroadcast;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeItem;
use BBC\ProgrammesPagesService\Domain\Entity\SegmentEvent;

class SegmentsPresenter extends Presenter
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
    private $live = null;

    public function __construct(
        LiveBroadcastHelper $liveBroadcastHelper,
        array $segmentEvents,
        ProgrammeItem $programmeItem,
        ?CollapsedBroadcast $collapsedBroadcast,
        array $options = []
    ) {
        parent::__construct($options);
        $this->segmentEvents = $segmentEvents;
        $this->liveBroadcastHelper = $liveBroadcastHelper;
        $this->programmeItem = $programmeItem;
        $this->collapsedBroadcast = $collapsedBroadcast;
        $this->options = $options;

        $this->segmentEvents = $this->getFilteredSegmentEvents();
        dump($segmentEvents);
        die;
    }

    private function getFilteredSegmentEvents(): array
    {
        if ($this->programmeItem->getOption('show_tracklist_inadvance')) {
            return $this->segmentEvents;
        }

        // if the programme item is currently being broadcast for the first time, filter to only the segment events that
        // have already started
        if ($this->collapsedBroadcast && !$this->collapsedBroadcast->isRepeat() && $this->isLive()) {
            $segmentEvents = [];
            $currentOffset = (ApplicationTime::getTime())->diff($this->collapsedBroadcast->getStartAt());

            $reverse = false;

            foreach ($this->segmentEvents as $segmentEvent) {
                if (!$reverse && $segmentEvent->getSegment()->getType() == 'music' && $segmentEvent->getOffset()) {
                    $reverse = true;
                }

                if (!$segmentEvent->getOffset() || $currentOffset <= $segmentEvent->getOffset()) {
                    $segmentEvent[] = $segmentEvent;
                }
            }

            // music segments that have offsets get reversed
            if ($reverse) {
                $segmentEvents = array_reverse($segmentEvents);
            }

            return $segmentEvents;
        }

        return $this->segmentEvents;
    }

    private function segmentEventsToSegmentItems(array $segmentEvents): array
    {
        $groups = [];

        $group = [];
        $previousTitle = reset($segmentEvents)->getTitle();

        // We use the index and call it 'relative offset' here because we could
        // have reversed the array in case of music segments for live programme debuts.
        foreach ($segmentEvents as $relativeOffset => $segmentEvent) {
            // null, empty or different titles mean new group
            if (empty($previousTitle) || $segmentEvent->getTitle() != $previousTitle) {
                $groups[] = $group;
                $group = [];
            } else {
                $group[] = $this->createSegmentItem($relativeOffset, $segmentEvent);
            }

            $previousTitle = $segmentEvent->getTitle();
        }

        // add the last group that didn't get added in the loop (if it's not empty)
        if ($group) {
            $groups[] = $group;
        }

        return $groups;
    }

    private function createSegmentItem(int $relativeOffset, SegmentEvent $segmentEvent): AbstractSegmentItem
    {
        if ($segmentEvent->getSegment()->getType() == 'music') {
            return new MusicSegmentItem();
        }

        return new SpeechSegmentItem();
    }

    private function isLive(): bool
    {
        if (is_null($this->live)) {
            $this->live = $this->collapsedBroadcast && $this->liveBroadcastHelper->isWatchableLive($this->collapsedBroadcast);
        }

        return $this->live;
    }
}
