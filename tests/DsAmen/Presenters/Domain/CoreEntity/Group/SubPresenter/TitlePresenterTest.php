<?php
declare(strict_types=1);

namespace Tests\App\DsAmen\Presenters\Domain\CoreEntity\Group\SubPresenter;

use App\DsAmen\Presenters\Domain\CoreEntity\Group\SubPresenter\TitlePresenter;
use App\DsShared\Helpers\StreamUrlHelper;
use App\DsShared\Helpers\TitleLogicHelper;
use BBC\ProgrammesPagesService\Domain\Entity\Gallery;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tests\App\DsAmen\Presenters\Domain\CoreEntity\BaseSubPresenterTest;

class TitlePresenterTest extends BaseSubPresenterTest
{
    /** @var UrlGeneratorInterface */
    private $router;

    /** @var  TitleLogicHelper|PHPUnit_Framework_MockObject_MockObject */
    private $mockTitleLogicHelper;

    public function setUp()
    {
        $this->router = $this->createRouter();
        $this->mockTitleLogicHelper = $this->createMock(TitleLogicHelper::class);
    }

    public function testGetUrlReturnsFindByPidRoute()
    {
        $gallery = $this->createConfiguredMock(Gallery::class, ['getPid' => new Pid('g0000001')]);
        $ctaPresenter = new TitlePresenter($this->createMock(StreamUrlHelper::class), $gallery, $this->router, $this->mockTitleLogicHelper);
        $this->assertSame('http://localhost/programmes/g0000001', $ctaPresenter->getUrl());
    }
}
