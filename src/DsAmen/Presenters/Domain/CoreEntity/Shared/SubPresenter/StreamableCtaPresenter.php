<?php
declare(strict_types = 1);

namespace App\DsAmen\Presenters\Domain\CoreEntity\Shared\SubPresenter;

use App\DsAmen\Presenters\Domain\CoreEntity\Base\SubPresenter\BaseCtaPresenter;
use BBC\ProgrammesPagesService\Domain\Entity\CoreEntity;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeItem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StreamableCtaPresenter extends BaseCtaPresenter
{
    protected $additionalOptions = [
        'show_duration' => true,
    ];

    public function __construct(CoreEntity $coreEntity, UrlGeneratorInterface $router, array $options = [])
    {
        $options = array_merge($this->additionalOptions, $options);
        parent::__construct($coreEntity, $router, $options);
    }

    public function getMediaIconName(): string
    {
        if ($this->coreEntity instanceof Episode) {
            if ($this->coreEntity->isAudio()) {
                return 'iplayer-radio';
            }

            return 'iplayer';
        }

        return 'play';
    }

    public function getMediaIconType(): string
    {
        return 'audio-visual';
    }

    public function getLinkLocation(): string
    {
        if ($this->coreEntity->isTv() && $this->getOption('force_iplayer_linking')) {
            return 'map_iplayer_calltoaction';
        }

        return $this->getOption('link_location_prefix') . 'calltoaction';
    }


    public function getLabelTranslation(): string
    {
        return '';
    }

    public function getUrl(): string
    {
        if ($this->coreEntity instanceof Episode && $this->coreEntity->isVideo()) {
            return $this->router->generate(
                'iplayer_play',
                ['pid' => $this->coreEntity->getPid()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }

        return $this->router->generate(
            'find_by_pid',
            ['pid' => $this->coreEntity->getPid()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    public function getDuration(): int
    {
        if ($this->showDuration()) {
            return $this->coreEntity->getDuration();
        }

        return 0;
    }

    private function showDuration(): bool
    {
        $isTvEpisode = ($this->coreEntity instanceof Episode && $this->coreEntity->isVideo());
        $isProgrammeItem = ($this->coreEntity instanceof ProgrammeItem);
        return ($isProgrammeItem && !$isTvEpisode && $this->getOption('show_duration') && $this->coreEntity->getDuration());
    }
}
