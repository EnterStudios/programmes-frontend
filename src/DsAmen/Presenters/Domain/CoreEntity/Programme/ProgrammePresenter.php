<?php
declare(strict_types = 1);

namespace App\DsAmen\Presenters\Domain\CoreEntity\Programme;

use App\DsAmen\Presenters\Domain\CoreEntity\Base\BaseCoreEntityPresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Base\SubPresenter\BaseBodyPresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Base\SubPresenter\BaseCtaPresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Base\SubPresenter\BaseImagePresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Base\SubPresenter\BaseTitlePresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Shared\SubPresenter\BodyPresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Shared\SubPresenter\ImagePresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Shared\SubPresenter\StreamableCtaPresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Shared\SubPresenter\TitlePresenter;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeItem;

class ProgrammePresenter extends BaseCoreEntityPresenter
{
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
        if ($this->isStreamable()) {
            $options = array_merge($this->subPresenterOptions('cta_options'), $options);
            if ($this->hideStandaloneDuration()) {
                $options['show_duration'] = false;
            }
            return new StreamableCtaPresenter(
                $this->coreEntity,
                $this->router,
                $options
            );
        }
        return null;
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
            $this->coreEntity,
            $this->router,
            $this->helperFactory->getTitleLogicHelper(),
            $options
        );
    }

    public function showStandaloneCta(): bool
    {
        return (!$this->getOption('show_image') && $this->isStreamable());
    }

    private function hideStandaloneDuration() :bool
    {
        return $this->showStandaloneCta() && $this->coreEntity instanceof ProgrammeItem && $this->coreEntity->isRadio();
    }
}
