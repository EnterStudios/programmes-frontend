<?php
declare(strict_types = 1);
namespace App\Ds2013\Page\Schedules\ByDayPage;

use App\Ds2013\Presenter;
use BBC\ProgrammesPagesService\Domain\Entity\Broadcast;
use BBC\ProgrammesPagesService\Domain\Entity\Service;
use Cake\Chronos\Chronos;
use DateTimeZone;

class SchedulesByDayPagePresenter extends Presenter
{
    /** @var Service */
    private $service;

    /** @var Chronos */
    private $startDate;

    /** @var Chronos */
    private $endDate;

    /** @var Broadcast[] */
    private $broadcasts;

    /** @var Service[] */
    private $servicesInNetwork;

    /** @var string|null */
    private $routeDate;

    /** @var Service */
    private $twinService;

    /** @var Broadcast */
    private $onAirBroadcast = false;

    public function __construct(
        Service $service,
        Chronos $startDate,
        Chronos $endDate,
        array $broadcasts,
        ?string $routeDate,
        array $servicesInNetwork,
        array $options = []
    ) {
        parent::__construct($options);
        $this->service = $service;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->broadcasts = $broadcasts;
        $this->routeDate = $routeDate;
        $this->servicesInNetwork = $servicesInNetwork;

        $this->twinService = $this->twinService();
    }

    public function getService(): Service
    {
        return $this->service;
    }

    public function getStartDate(): Chronos
    {
        return $this->startDate;
    }

    public function getEndDate(): Chronos
    {
        return $this->endDate;
    }

    public function getRouteDate(): ?string
    {
        return $this->routeDate;
    }

    public function getServicesInNetwork(): array
    {
        return $this->servicesInNetwork;
    }

    public function getServicesHeadingMessage()
    {
        return $this->service->isTv() ? 'schedules_regional_note' : 'schedules_regional_note_radio';
    }

    public function getSiblingServicesLinkMessage(): string
    {
        if ($this->twinService) {
            return 'schedules_regional_changeto';
        }
        return $this->service->isTv() ? 'schedules_regional' : 'schedules_regional_change';
    }

    public function getSiblingServicesLinkName(): string
    {
        return $this->twinService ? $this->twinService->getName() : $this->service->getNetwork()->getName();
    }

    public function getTwinServicePid(): string
    {
        return $this->twinService ? (string) $this->twinService->getPid() : '';
    }

    public function getBroadcastsGroupedByPeriodOfDay(): array
    {
        $intervalsDay = [
            'early' => [],
            'morning' => [],
            'afternoon' => [],
            'evening' => [],
            'late' => [],
        ];

        //$prior_broadcast = null;
        foreach ($this->broadcasts as $broadcast) {
            // // If the end of the prior is earlier than the start of this broadcast
            // // then inject a broadcast gap object.
            // if ($prior_broadcast && $prior_broadcast->end->compare($broadcast->start) == -1) {
            //     $period = $this->_getBroadcastPeriod($prior_broadcast->end, $day, $use_timezones);
            //     $periods_of_day[$period][] = $this->_broadcastGap($prior_broadcast->end, $broadcast->start);
            // }

            $period = $this->getBroadcastPeriodWord($broadcast, $this->startDate);
            $intervalsDay[$period][] = $broadcast;
        }

        return array_filter($intervalsDay);
    }

    public function getOnAirBroadcast(): ?Broadcast
    {
        if ($this->onAirBroadcast !== false) {
            return $this->onAirBroadcast;
        }
        $now = Chronos::now('Europe/London');

        $this->onAirBroadcast = null;
        foreach ($this->broadcasts as $broadcast) {
            if ($broadcast->isOnAirAt($now)) {
                $this->onAirBroadcast = $broadcast;
                break;
            }
        }
        return $this->onAirBroadcast;
    }

    public function hasBroadcasts(): bool
    {
        return !!$this->broadcasts;
    }

    public function hasJumpLinks(): bool
    {
        return !!$this->broadcasts && !$this->service->isTv();
    }

    public function hasSiblingServiceLink(): bool
    {
        return count($this->servicesInNetwork) > 1;
    }

    public function hasSiblingServiceList(): bool
    {
        return count($this->servicesInNetwork) > 2;
    }

    /**
     * Early - midnight until 6am
     * Morning - 6am until midday
     * Afternoon - midday until 6pm
     * Evening - 6pm until midnight
     * Late - midnight until 6am the next day
     *
     * @param Broadcast $broadcast
     * @param Chronos $selectedDate
     * @return string
     */
    private function getBroadcastPeriodWord(Broadcast $broadcast, Chronos $selectedDate): string
    {
        $selectedDayEnd = $selectedDate->endOfDay();

        $startBroadcast = $broadcast->getStartAt()->setTimezone(new DateTimeZone('Europe/London'));
        $startBroadcastHour = $startBroadcast->format('H');

        // Need to check for 'late' first as these broadcasts are actually the day after the selected date
        if ($startBroadcast > $selectedDayEnd) {
            return 'late';
        }

        if ($startBroadcastHour < 6) {
            return 'early';
        }

        if ($startBroadcastHour < 12) {
            return 'morning';
        }

        if ($startBroadcastHour < 18) {
            return 'afternoon';
        }

        return 'evening';
    }

    private function twinService(): ?Service
    {
        if (count($this->servicesInNetwork) != 2) {
            return null;
        }

        // If there are two services, find the "other" service
        $otherServices = array_filter($this->servicesInNetwork, function (Service $sisterService) {
            return ($this->service->getSid() != $sisterService->getSid());
        });
        return reset($otherServices);
    }
}