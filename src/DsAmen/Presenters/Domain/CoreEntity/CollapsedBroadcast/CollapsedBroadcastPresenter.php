<?php
declare(strict_types = 1);

namespace App\DsAmen\Presenters\Domain\CoreEntity\CollapsedBroadcast;

use App\DsAmen\Presenters\Domain\CoreEntity\Base\BaseCoreEntityPresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Base\SubPresenter\BaseBodyPresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Base\SubPresenter\BaseCtaPresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Base\SubPresenter\BaseImagePresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Base\SubPresenter\BaseTitlePresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\CollapsedBroadcast\SubPresenter\DetailsPresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\CollapsedBroadcast\SubPresenter\LiveCtaPresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Shared\SubPresenter\BodyPresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Shared\SubPresenter\ImagePresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Shared\SubPresenter\StreamableCtaPresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Shared\SubPresenter\TitlePresenter;
use App\DsShared\Helpers\HelperFactory;
use App\DsShared\Helpers\StreamUrlHelper;
use App\Translate\TranslateProvider;
use BBC\ProgrammesPagesService\Domain\Entity\CollapsedBroadcast;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeItem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CollapsedBroadcastPresenter extends BaseCoreEntityPresenter
{
    /** @var CollapsedBroadcast */
    private $collapsedBroadcast;

    /** @var StreamUrlHelper */
    private $streamUrlHelper;

    /** @var TranslateProvider */
    private $translateProvider;

    /** @var bool */
    protected $playableLive;

    protected $additionalOptions = [
        'advanced_live' => false,
    ];

    public function __construct(
        CollapsedBroadcast $collapsedBroadcast,
        UrlGeneratorInterface $router,
        TranslateProvider $translateProvider,
        HelperFactory $helperFactory,
        array $options = []
    ) {
        $options = array_merge($this->additionalOptions, $options);
        parent::__construct($collapsedBroadcast->getProgrammeItem(), $router, $helperFactory, $options);

        $this->collapsedBroadcast = $collapsedBroadcast;
        $this->streamUrlHelper = $this->helperFactory->getStreamUrlHelper();
        $this->translateProvider = $translateProvider;
    }

    public function getBodyPresenter(array $options = []): BaseBodyPresenter
    {
        $options = array_merge($this->subPresenterOptions('body_options'), $options);
        return new BodyPresenter(
            $this->coreEntity,
            $options
        );
    }

    public function getCtaPresenter(array $options = []): ?BaseCtaPresenter
    {
        $options = array_merge($this->subPresenterOptions('cta_options'), $options);
        if ($this->isPlayableLive()) {
            return new LiveCtaPresenter(
                $this->collapsedBroadcast,
                $this->router,
                $this->helperFactory->getLiveBroadcastHelper(),
                $options
            );
        }
        if ($this->isStreamable()) {
            if ($this->hideStandaloneDuration()) {
                $options['show_duration'] = false;
            }

            return new StreamableCtaPresenter(
                $this->streamUrlHelper,
                $this->coreEntity,
                $this->router,
                $options
            );
        }
        return null;
    }

    public function getDetailsPresenter(): DetailsPresenter
    {
        return new DetailsPresenter(
            $this->collapsedBroadcast,
            $this->translateProvider,
            $this->helperFactory->getLocalisedDaysAndMonthsHelper(),
            $this->helperFactory->getBroadcastNetworksHelper(),
            $this->options
        );
    }

    public function getImagePresenter(array $options = []): BaseImagePresenter
    {
        $options = array_merge($this->subPresenterOptions('image_options'), $options);
        $options['cta_options'] = $this->mergeWithSubPresenterOptions($options, 'cta_options');
        return new ImagePresenter(
            $this->coreEntity,
            $this->router,
            $this->getCtaPresenter(),
            $options
        );
    }

    public function getTitlePresenter(array $options = []): BaseTitlePresenter
    {
        $options = array_merge($this->subPresenterOptions('title_options'), $options);
        return new TitlePresenter(
            $this->streamUrlHelper,
            $this->coreEntity,
            $this->router,
            $this->helperFactory->getTitleLogicHelper(),
            $options
        );
    }

    public function showStandaloneCta(): bool
    {
        return !$this->getOption('show_image') && ($this->isStreamable() || $this->isPlayableLive());
    }

    public function showWatchFromStartCta(): bool
    {
        return !($this->coreEntity instanceof ProgrammeItem && $this->coreEntity->isRadio()) && $this->isPlayableLive();
    }

    public function getShowFromStartCtaPresenter(array $options = [])
    {
        $defaultOptions = ['link_to_start' =>  true];
        $options = array_merge($defaultOptions, $this->subPresenterOptions('cta_options'), $options);
        return new LiveCtaPresenter(
            $this->collapsedBroadcast,
            $this->router,
            $this->helperFactory->getLiveBroadcastHelper(),
            $options
        );
    }

    protected function isPlayableLive(): bool
    {
        if ($this->playableLive === null) {
            $this->playableLive = $this->helperFactory->getLiveBroadcastHelper()->isWatchableLive(
                $this->collapsedBroadcast,
                $this->options['advanced_live']
            );
        }
        return $this->playableLive;
    }

    private function hideStandaloneDuration() :bool
    {
        return $this->showStandaloneCta() && $this->collapsedBroadcast->getProgrammeItem() instanceof ProgrammeItem && $this->collapsedBroadcast->getProgrammeItem()->isRadio();
    }
}
