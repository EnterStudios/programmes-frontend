<?php
declare(strict_types = 1);

namespace Tests\App\Ds2013\Presenters\Domain\CoreEntity\Programme\SubPresenters;

use App\Ds2013\Presenters\Domain\CoreEntity\Programme\SubPresenters\ProgrammeOverlayPresenter;
use App\DsShared\Helpers\PlayTranslationsHelper;
use App\DsShared\Helpers\StreamUrlHelper;
use BBC\ProgrammesPagesService\Domain\Entity\Clip;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeItem;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollectionBuilder;

class ProgrammeOverlayPresenterTest extends TestCase
{
    private $router;

    private $mockTranslationsHelper;
    private $mockStreamUrlHelper;

    public function setUp()
    {
        $routeCollectionBuilder = new RouteCollectionBuilder();
        $routeCollectionBuilder->add('/programmes/{pid}', '', 'find_by_pid');
        $routeCollectionBuilder->add('/iplayer/episode/{pid}', '', 'iplayer_play');

        $this->router = new UrlGenerator(
            $routeCollectionBuilder->build(),
            new RequestContext()
        );

        $this->mockTranslationsHelper = $this->createMock(PlayTranslationsHelper::class);
        $this->mockStreamUrlHelper = $this->createMock(StreamUrlHelper::class);
    }

    /**
     * @dataProvider mediaIconNameProvider
     */
    public function testGetMediaIconName($isRadio, $expectedResult)
    {
        $programme = $this->createMock(Episode::class);
        $programme->expects($this->once())
            ->method('isRadio')
            ->willReturn($isRadio);
        $programmeOverlayPresenter = new ProgrammeOverlayPresenter(
            $this->router,
            $this->mockTranslationsHelper,
            $this->mockStreamUrlHelper,
            $programme
        );
        $this->assertEquals($expectedResult, $programmeOverlayPresenter->getMediaIconName());
    }

    public function mediaIconNameProvider()
    {
        return [
            [true, 'iplayer-radio'],
            [false, 'iplayer'],
        ];
    }

    public function testGetPlaybackUrlIplayer()
    {
        $this->mockStreamUrlHelper->expects($this->once())->method('getRouteForProgrammeItem')->willReturn('iplayer_play');
        $programmeItem = $this->playbackUrlProgramme(Episode::class, 'b0000002');
        $programmeOverlayPresenter = new ProgrammeOverlayPresenter(
            $this->router,
            $this->mockTranslationsHelper,
            $this->mockStreamUrlHelper,
            $programmeItem
        );
        $this->assertEquals('http://localhost/iplayer/episode/b0000002', $programmeOverlayPresenter->getPlaybackUrl());
    }

    public function testGetPlaybackUrlProgrammes()
    {
        $programmeItem = $this->playbackUrlProgramme(Episode::class, 'b0000001');
        $this->mockStreamUrlHelper->expects($this->once())->method('getRouteForProgrammeItem')->willReturn('find_by_pid');
        $programmeOverlayPresenter = new ProgrammeOverlayPresenter(
            $this->router,
            $this->mockTranslationsHelper,
            $this->mockStreamUrlHelper,
            $programmeItem
        );
        $this->assertEquals('http://localhost/programmes/b0000001#play', $programmeOverlayPresenter->getPlaybackUrl());
    }

    private function playbackUrlProgramme(string $type, string $pid)
    {
        $programme = $this->createMock($type);
        $programme->method('getPid')->willReturn(new Pid($pid));
        return $programme;
    }
}
