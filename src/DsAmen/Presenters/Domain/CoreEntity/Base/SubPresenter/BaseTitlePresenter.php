<?php
declare(strict_types = 1);

namespace App\DsAmen\Presenters\Domain\CoreEntity\Base\SubPresenter;

use App\DsAmen\Presenter;
use App\DsShared\Helpers\StreamUrlHelper;
use App\DsShared\Helpers\TitleLogicHelper;
use App\Exception\InvalidOptionException;
use BBC\ProgrammesPagesService\Domain\Entity\CoreEntity;
use BBC\ProgrammesPagesService\Domain\Entity\Group;
use BBC\ProgrammesPagesService\Domain\Entity\Programme;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeItem;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class BaseTitlePresenter extends Presenter
{
    /** @var UrlGeneratorInterface */
    protected $router;

    /** @var TitleLogicHelper */
    protected $titleHelper;

    /** @var Group|ProgrammeItem */
    protected $coreEntity;

    /** @var CoreEntity */
    protected $mainTitleProgramme;

    /** @var StreamUrlHelper */
    protected $streamUrlHelper;

    /** @var CoreEntity[] */
    protected $subTitlesProgrammes;

    /** @var array */
    protected $options = [
        'context_programme' => null,
        'h_tag' => 'h4',
        'text_colour_on_title_link' => true,
        'title_format' => 'item::ancestry',
        'title_size_large' => 'gel-pica-bold',
        'title_size_small' => 'gel-pica',
        'branding_name' => 'subtle',
        'truncation_length' => null,
    ];

    public function __construct(
        StreamUrlHelper $streamUrlHelper,
        CoreEntity $coreEntity,
        UrlGeneratorInterface $router,
        TitleLogicHelper $titleHelper,
        array $options = []
    ) {
        parent::__construct($options);

        $this->router = $router;
        $this->streamUrlHelper = $streamUrlHelper;
        $this->titleHelper = $titleHelper;
        $this->coreEntity = $coreEntity;
    }

    public function getBrandingClass(): string
    {
        if (!$this->getOption('branding_name') || !$this->getOption('text_colour_on_title_link')) {
            return '';
        }

        return 'br-' . $this->getOption('branding_name') . '-text-ontext';
    }

    public function getLinkLocationPrefix(): string
    {
        if ($this->coreEntity->isTv() && $this->getOption('force_iplayer_linking')) {
            return 'map_iplayer_';
        }
        return $this->getOption('link_location_prefix');
    }

    public function getMainTitle(): string
    {
        if (!isset($this->mainTitleProgramme)) {
            $this->setTitleProgrammes();
        }

        return $this->truncate($this->mainTitleProgramme->getTitle());
    }

    public function getSubTitle(): string
    {
        if (!isset($this->subTitlesProgrammes)) {
            $this->setTitleProgrammes();
        }

        return $this->truncate(
            implode(', ', array_map(function (CoreEntity $programme) {
                return $programme->getTitle();
            }, $this->subTitlesProgrammes))
        );
    }

    public function getUrl(): string
    {
        $route = $this->streamUrlHelper->getRouteForProgrammeItem($this->coreEntity);
        if (!$this->getOption('force_iplayer_linking')) {
            $route = 'find_by_pid';
        }

        return $this->router->generate($route, ['pid' => $this->coreEntity->getPid()], UrlGenerator::ABSOLUTE_URL);
    }

    protected function validateOptions(array $options): void
    {
        if (isset($options['context_programme']) && !($options['context_programme'] instanceof Programme)) {
            throw new InvalidOptionException('context_programme option must be null or a Programme domain object');
        }

        if (!is_bool($options['text_colour_on_title_link'])) {
            throw new InvalidOptionException('text_colour_on_title_link option must be a boolean');
        }

        if (isset($options['truncation_length']) && !is_int($options['truncation_length'])) {
            throw new InvalidOptionException('truncation_length option must be null or an integer. HINT: use null for unlimited title length');
        }
    }

    private function setTitleProgrammes(): void
    {
        [$this->mainTitleProgramme, $this->subTitlesProgrammes] = $this->titleHelper->getOrderedProgrammesForTitle(
            $this->coreEntity,
            $this->options['context_programme'],
            $this->options['title_format']
        );
    }

    private function truncate(string $string, string $suffix = '…'): string
    {
        $length = mb_strlen($string);
        $maxLength = $this->getOption('truncation_length');

        if ($maxLength > 0 && $length > $maxLength) {
            return mb_substr($string, 0, mb_strrpos($string, ' ', $maxLength - $length)) . $suffix;
        }

        return $string;
    }
}
