<?php
declare(strict_types = 1);
namespace App\Ds2013\Presenters\Domain\CoreEntity\Programme;

use App\Ds2013\Presenters\Domain\CoreEntity\Programme\SubPresenters\ProgrammeBodyPresenter;
use App\Ds2013\Presenters\Domain\CoreEntity\Programme\SubPresenters\ProgrammeOverlayPresenter;
use App\Ds2013\Presenters\Domain\CoreEntity\SharedSubPresenters\CoreEntityTitlePresenter;
use App\DsShared\Helpers\HelperFactory;
use App\Exception\InvalidOptionException;
use BBC\ProgrammesPagesService\Domain\Entity\Brand;
use BBC\ProgrammesPagesService\Domain\Entity\Clip;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use BBC\ProgrammesPagesService\Domain\Entity\Programme;
use BBC\ProgrammesPagesService\Domain\Entity\Series;
use RuntimeException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProgrammePresenter extends ProgrammePresenterBase
{
    protected $options = [
        'branding_context' => 'page',
        'container_classes' => '',
        'highlight_box_classes' => '',
        'outer_html_attributes' => [],
        'context_programme' => null,
        'truncation_length' => null,
        'image_options' => [],
        'title_options' => [],
        'body_options' => [],
    ];

    /**
     * Define the set of options that are needed by all sub-presenters too
     *
     * @var array
     */
    protected $sharedOptions = [
        'branding_context',
        'context_programme',
        'truncation_length',
    ];

    /** @var HelperFactory */
    protected $helperFactory;

    public function __construct(
        UrlGeneratorInterface $router,
        HelperFactory $helperFactory,
        Programme $programme,
        array $options = []
    ) {
        parent::__construct($router, $programme, $options);
        $this->options['outer_html_attributes']['data-pid'] = (string) $programme->getPid();
        $this->helperFactory = $helperFactory;
    }

    /**
     * This is way too complex to put in the view. So the array to push to build_css_classes
     * is built here.
     */
    public function getOuterDivClasses(): array
    {
        $brandingContext = $this->getOption('branding_context');
        $programmeType = $this->programmeTypeAsString($this->programme);
        $isClip = ($this->programme instanceof Clip);
        $containerClasses = $this->getOption('container_classes');
        $highlightBoxClasses = $this->getOption('highlight_box_classes');

        return [
            'programme' => true,
            'programme--tv' => ($this->programme->isTv() && !$isClip),
            'programme--radio' => ($this->programme->isRadio() && !$isClip),
            "programme--$programmeType" => true,
            'block-link' => true,
            $containerClasses => (!empty($containerClasses)),
            $highlightBoxClasses => (!empty($highlightBoxClasses)),
            'br-keyline' => (!empty($highlightBoxClasses)),
            "br-blocklink-$brandingContext" => (!empty($highlightBoxClasses)),
            "br-$brandingContext-linkhover-onbg015--hover" => (!empty($highlightBoxClasses)),
        ];
    }

    public function getProgrammeOverlayPresenter(): ProgrammeOverlayPresenter
    {
        return new ProgrammeOverlayPresenter(
            $this->router,
            $this->helperFactory->getPlayTranslationsHelper(),
            $this->helperFactory->getStreamUrlHelper(),
            $this->programme,
            $this->subPresenterOptions('image_options')
        );
    }

    public function getProgrammeTitlePresenter(): CoreEntityTitlePresenter
    {
        return new CoreEntityTitlePresenter(
            $this->router,
            $this->helperFactory->getTitleLogicHelper(),
            $this->programme,
            $this->subPresenterOptions('title_options')
        );
    }

    public function getProgrammeBodyPresenter(): ProgrammeBodyPresenter
    {
        return new ProgrammeBodyPresenter(
            $this->router,
            $this->helperFactory->getPlayTranslationsHelper(),
            $this->programme,
            $this->subPresenterOptions('body_options')
        );
    }

    /**
     * Sub-presenters require some of the same options as their parents, which options is defined
     * in $this->sharedOptions
     */
    protected function subPresenterOptions(string $optionsKey): array
    {
        $options = $this->options[$optionsKey];
        foreach ($this->sharedOptions as $sharedOptionKey) {
            $options[$sharedOptionKey] = $this->options[$sharedOptionKey];
        }
        return $options;
    }

    protected function validateOptions(array $options): void
    {
        if (isset($options['context_programme']) && ! $options['context_programme'] instanceof Programme) {
            throw new InvalidOptionException('context_programme option must be null or a Programme domain object');
        }
    }

    protected function programmeTypeAsString(Programme $programme): string
    {
        switch ($programme) {
            case ($programme instanceof Series):
                return 'series';
            case ($programme instanceof Brand):
                return 'brand';
            case ($programme instanceof Episode):
                return 'episode';
            case ($programme instanceof Clip):
                return 'clip';
            default:
                throw new RuntimeException('typeFromProgramme expected a programme but received other type.');
        }
    }
}
