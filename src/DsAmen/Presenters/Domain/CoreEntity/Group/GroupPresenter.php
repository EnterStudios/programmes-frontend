<?php
declare(strict_types = 1);

namespace App\DsAmen\Presenters\Domain\CoreEntity\Group;

use App\DsAmen\Presenters\Domain\CoreEntity\Base\BaseCoreEntityPresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Base\SubPresenter\BaseBodyPresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Base\SubPresenter\BaseCtaPresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Base\SubPresenter\BaseImagePresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Base\SubPresenter\BaseTitlePresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Group\SubPresenter\MediaIconCtaPresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Group\SubPresenter\TitlePresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Shared\SubPresenter\BodyPresenter;
use App\DsAmen\Presenters\Domain\CoreEntity\Shared\SubPresenter\ImagePresenter;

class GroupPresenter extends BaseCoreEntityPresenter
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
        $options = array_merge($this->subPresenterOptions('cta_options'), $options);
        return new MediaIconCtaPresenter(
            $this->coreEntity,
            $this->router,
            $options
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
            $this->helperFactory->getStreamUrlHelper(),
            $this->coreEntity,
            $this->router,
            $this->helperFactory->getTitleLogicHelper(),
            $options
        );
    }
}
