<?php
declare(strict_types = 1);
namespace App\Ds2013\Presenters\Domain\CoreEntity\Programme\CollapsedBroadcastSubPresenters;

use App\Ds2013\Presenters\Domain\CoreEntity\Programme\SubPresenters\ProgrammeOverlayPresenter;
use App\DsShared\Helpers\LiveBroadcastHelper;
use App\DsShared\Helpers\PlayTranslationsHelper;
use App\DsShared\Helpers\StreamUrlHelper;
use BBC\ProgrammesPagesService\Domain\Entity\CollapsedBroadcast;
use BBC\ProgrammesPagesService\Domain\Entity\Programme;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CollapsedBroadcastProgrammeOverlayPresenter extends ProgrammeOverlayPresenter
{
    /** @var CollapsedBroadcast */
    private $collapsedBroadcast;

    /** @var LiveBroadcastHelper */
    private $liveBroadcastHelper;

    public function __construct(
        UrlGeneratorInterface $router,
        PlayTranslationsHelper $playTranslationsHelper,
        LiveBroadcastHelper $liveBroadcastHelper,
        StreamUrlHelper $streamUrlHelper,
        CollapsedBroadcast $broadcast,
        Programme $programme,
        array $options = []
    ) {
        parent::__construct($router, $playTranslationsHelper, $streamUrlHelper, $programme, $options);
        $this->collapsedBroadcast = $broadcast;
        $this->liveBroadcastHelper = $liveBroadcastHelper;
    }

    public function getSimulcastUrl(): string
    {
        return $this->liveBroadcastHelper->simulcastUrl($this->collapsedBroadcast, $this->options['context_service']);
    }

    public function isWatchableLive(): bool
    {
        return $this->liveBroadcastHelper->isWatchableLive($this->collapsedBroadcast, $this->options['advanced_live']);
    }

    public function translatePlayLive(): string
    {
        return $this->playTranslationsHelper->translatePlayLive($this->programme, $this->options['context_service']);
    }

    public function getTemplateVariableName(): string
    {
        return 'programme_overlay';
    }
}
