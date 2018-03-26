<?php
declare(strict_types = 1);
namespace App\Ds2013\Presenters\Domain\CoreEntity\Programme;

use App\Ds2013\Presenters\Domain\CoreEntity\Programme\BroadcastSubPresenters\BroadcastProgrammeTitlePresenter;
use App\Ds2013\Presenters\Domain\CoreEntity\Programme\CollapsedBroadcastSubPresenters\CollapsedBroadcastProgrammeBodyPresenter;
use App\Ds2013\Presenters\Domain\CoreEntity\Programme\CollapsedBroadcastSubPresenters\CollapsedBroadcastProgrammeOverlayPresenter;
use App\Ds2013\Presenters\Domain\CoreEntity\Programme\SubPresenters\ProgrammeBodyPresenter;
use App\Ds2013\Presenters\Domain\CoreEntity\Programme\SubPresenters\ProgrammeOverlayPresenter;
use App\Ds2013\Presenters\Domain\CoreEntity\SharedSubPresenters\CoreEntityTitlePresenter;
use App\DsShared\Helpers\HelperFactory;
use App\Exception\InvalidOptionException;
use BBC\ProgrammesPagesService\Domain\Entity\CollapsedBroadcast;
use BBC\ProgrammesPagesService\Domain\Entity\Programme;
use BBC\ProgrammesPagesService\Domain\Entity\Service;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * This is an override of the programme presenter that handles
 * cases where we wish to display broadcast (watch live/services)
 * information alongside a programme.
 */
class CollapsedBroadcastProgrammePresenter extends ProgrammePresenter
{
    /** @var CollapsedBroadcast */
    private $collapsedBroadcast;

    private $additionalOptions = [
        'advanced_live' => false,
        'context_service' => null,
    ];

    const TEMPLATE_PATH_CLASS_OVERRIDE = ProgrammePresenter::class;

    /**
     * Define options needed by all collapsed broadcast sub-presenters too
     *
     * @var array
     */
    private $additionalSharedOptions = [
        'advanced_live',
        'context_service',
    ];

    public function __construct(
        UrlGeneratorInterface $router,
        HelperFactory $helperFactory,
        CollapsedBroadcast $collapsedBroadcast,
        Programme $programme,
        array $options = []
    ) {
        if (isset($options['context_service']) && !($options['context_service'] instanceof Service)) {
            throw new InvalidOptionException("context_service option must be null or a Service domain object");
        }
        $options = array_merge($this->additionalOptions, $options);
        $this->sharedOptions = array_merge($this->sharedOptions, $this->additionalSharedOptions);
        parent::__construct($router, $helperFactory, $programme, $options);
        $this->collapsedBroadcast = $collapsedBroadcast;
    }

    public function getProgrammeOverlayPresenter(): ProgrammeOverlayPresenter
    {
        return new CollapsedBroadcastProgrammeOverlayPresenter(
            $this->router,
            $this->helperFactory->getPlayTranslationsHelper(),
            $this->helperFactory->getLiveBroadcastHelper(),
            $this->helperFactory->getStreamUrlHelper(),
            $this->collapsedBroadcast,
            $this->programme,
            $this->subPresenterOptions('image_options')
        );
    }

    public function getProgrammeTitlePresenter(): CoreEntityTitlePresenter
    {
        return new BroadcastProgrammeTitlePresenter(
            $this->router,
            $this->helperFactory->getTitleLogicHelper(),
            $this->collapsedBroadcast,
            $this->programme,
            $this->subPresenterOptions('title_options')
        );
    }

    public function getProgrammeBodyPresenter(): ProgrammeBodyPresenter
    {
        return new CollapsedBroadcastProgrammeBodyPresenter(
            $this->router,
            $this->helperFactory->getPlayTranslationsHelper(),
            $this->helperFactory->getLiveBroadcastHelper(),
            $this->collapsedBroadcast,
            $this->programme,
            $this->subPresenterOptions('body_options')
        );
    }

    public function getTemplateVariableName(): string
    {
        return 'programme';
    }
}
