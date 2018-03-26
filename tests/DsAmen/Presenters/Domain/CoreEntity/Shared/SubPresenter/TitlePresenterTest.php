<?php
declare(strict_types=1);

namespace Tests\App\DsAmen\Presenters\Domain\CoreEntity\Shared\SubPresenter;

use App\DsAmen\Presenters\Domain\CoreEntity\Shared\SubPresenter\TitlePresenter;
use App\DsShared\Helpers\StreamUrlHelper;
use App\DsShared\Helpers\TitleLogicHelper;
use BBC\ProgrammesPagesService\Domain\Entity\Brand;
use BBC\ProgrammesPagesService\Domain\Entity\Clip;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use BBC\ProgrammesPagesService\Domain\Entity\Programme;
use BBC\ProgrammesPagesService\Domain\Entity\Series;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tests\App\DsAmen\Presenters\Domain\CoreEntity\BaseSubPresenterTest;

class TitlePresenterTest extends BaseSubPresenterTest
{
    /** @var Clip|PHPUnit_Framework_MockObject_MockObject */
    private $mockClip;

    /** @var Brand|PHPUnit_Framework_MockObject_MockObject */
    private $mockContext;

    /** @var UrlGeneratorInterface */
    private $router;

    /** @var TitleLogicHelper|PHPUnit_Framework_MockObject_MockObject */
    private $mockTitleLogicHelper;

    protected function setUp()
    {
        $this->mockClip = $this->createMockClip();
        $this->mockContext = $this->createMockBrand();
        $this->mockTitleLogicHelper = $this->createMock(TitleLogicHelper::class);
        $this->router = $this->createRouter();
    }

    /** @dataProvider getBrandingClassProvider */
    public function testGetBrandingClass(string $brandingName, bool $textColourOnTitleLink, string $expected): void
    {
        $titlePresenter = new TitlePresenter(
            $this->createMock(StreamUrlHelper::class),
            $this->mockClip,
            $this->router,
            $this->mockTitleLogicHelper,
            ['branding_name' => $brandingName, 'text_colour_on_title_link' => $textColourOnTitleLink]
        );

        $this->assertSame($expected, $titlePresenter->getBrandingClass());
    }

    public function getBrandingClassProvider(): array
    {
        return [
            'No branding return empty' => ['', true, ''],
            'No text colour on title link return empty' => ['secondary', false, ''],
            'Branding and text colour on title link return class' => ['secondary', true, 'br-secondary-text-ontext'],
        ];
    }

    /** @dataProvider getLinkLocationPrefixProvider */
    public function testGetLinkLocationPrefix(bool $forceIplayerLinking, bool $isTv, string $expected): void
    {
        $this->mockClip->method('isTv')->willReturn($isTv);

        $titlePresenter = new TitlePresenter(
            $this->createMock(StreamUrlHelper::class),
            $this->mockClip,
            $this->router,
            $this->mockTitleLogicHelper,
            [
                'force_iplayer_linking' => $forceIplayerLinking,
                'link_location_prefix' => 'programmeobject_',
            ]
        );

        $this->assertSame($expected, $titlePresenter->getLinkLocationPrefix());
    }

    public function getLinkLocationPrefixProvider(): array
    {
        return [
            'Forcing iPlayer linking on TV Clip' => [true, true, 'map_iplayer_'],
            'Not forcing iPlayer linking on TV Clip' => [false, true, 'programmeobject_'],
            'Forcing iPlayer linking on non-TV Clip' => [true, false, 'programmeobject_'],
        ];
    }

    /** @dataProvider getMainAndSubTitlesProvider */
    public function testGetMainAndSubTitles(array $titleAncestry, string $subTitle): void
    {
        $this->mockTitleLogicHelper
            ->method('getOrderedProgrammesForTitle')
            ->with($this->mockClip, $this->mockContext, 'item::ancestry')
            ->willReturn([$this->mockClip, $titleAncestry]);

        $titlePresenter = new TitlePresenter(
            $this->createMock(StreamUrlHelper::class),
            $this->mockClip,
            $this->router,
            $this->mockTitleLogicHelper,
            [
                'context_programme' => $this->mockContext,
                'title_format' => 'item::ancestry',
            ]
        );

        $this->assertSame('Clip 1', $titlePresenter->getMainTitle());
        $this->assertSame($subTitle, $titlePresenter->getSubTitle());
    }

    public function getMainAndSubTitlesProvider(): array
    {
        $mockSeries = $this->createMock(Series::class);
        $mockSeries->method('getTitle')->willReturn('Series 1');

        $mockEpisode = $this->createMock(Episode::class);
        $mockEpisode->method('getTitle')->willReturn('Episode 1');

        return [
            'No parents' => [[], ''],
            'One parent' => [[$mockEpisode], 'Episode 1'],
            'Multiple parents' => [[$mockSeries, $mockEpisode], 'Series 1, Episode 1'],
        ];
    }

    public function testLongerThan60CharacterLongMainAndSubTitles(): void
    {
        $mockSeries = $this->createMock(Series::class);
        $mockSeries->method('getTitle')->willReturn('Very Very Very Very Long Series Name');

        $mockEpisode = $this->createMock(Episode::class);
        $mockEpisode->method('getTitle')->willReturn('Very Very Very Very Long Episode Name');

        $mockClip = $this->createMock(Clip::class);
        $mockClip->method('getTitle')->willReturn('Very Very Very Very Very Very Very Very Very Very Long Clip Name');

        $this->mockTitleLogicHelper
            ->method('getOrderedProgrammesForTitle')
            ->with($mockClip, $this->mockContext, 'item::ancestry')
            ->willReturn([$mockClip, [$mockSeries, $mockEpisode]]);

        $titlePresenter = new TitlePresenter(
            $this->createMock(StreamUrlHelper::class),
            $this->mockClip,
            $this->router,
            $this->mockTitleLogicHelper,
            [
                'context_programme' => $this->mockContext,
                'title_format' => 'item::ancestry',
                'truncation_length' => 60,
            ]
        );

        $this->assertSame('Very Very Very Very Very Very Very Very Very Very Long Clip…', $titlePresenter->getMainTitle());
        $this->assertSame('Very Very Very Very Long Series Name, Very Very Very Very…', $titlePresenter->getSubTitle());
    }

    public function testLongerThan60CharacterLongMainTitleWithMultiByteString(): void
    {
        $mockClip = $this->createMock(Clip::class);
        $mockClip->method('getTitle')->willReturn(
            '王勃《送杜少府之任蜀州》城阙辅三秦, 风烟望五津. 与君离别意, 同是宦游人. 海内存知己, 天涯若比邻. 无为在岐路, 儿女共沾巾. 王勃《送杜少府之任蜀州》'
        );

        $this->mockTitleLogicHelper
            ->method('getOrderedProgrammesForTitle')
            ->with($mockClip, $this->mockContext, 'item::ancestry')
            ->willReturn([$mockClip, []]);

        $titlePresenter = new TitlePresenter(
            $this->createMock(StreamUrlHelper::class),
            $this->mockClip,
            $this->router,
            $this->mockTitleLogicHelper,
            [
                'context_programme' => $this->mockContext,
                'title_format' => 'item::ancestry',
                'truncation_length' => 60,
            ]
        );

        $this->assertSame(
            '王勃《送杜少府之任蜀州》城阙辅三秦, 风烟望五津. 与君离别意, 同是宦游人. 海内存知己, 天涯若比邻. 无为在岐路,…',
            $titlePresenter->getMainTitle()
        );
    }

    /** @dataProvider getUrlProvider */
    public function testGetUrl(Programme $programme, bool $forceIplayerLinking, string $expected): void
    {
        $streamUrlHelper = $this->createMock(StreamUrlHelper::class);
        if ($forceIplayerLinking) {
            $streamUrlHelper->expects($this->once())->method('getRouteForProgrammeItem')->willReturn('iplayer_play');
        }
        $titlePresenter = new TitlePresenter(
            $streamUrlHelper,
            $programme,
            $this->router,
            $this->mockTitleLogicHelper,
            [
                'context_programme' => $this->mockContext,
                'title_format' => 'item::ancestry',
                'force_iplayer_linking' => $forceIplayerLinking,
            ]
        );

        $this->assertSame($expected, $titlePresenter->getUrl());
    }

    public function getUrlProvider(): array
    {
        $tvEpisode = $this->createMockTvEpisode();
        $clip = $this->createMockClip();
        $brand = $this->createMockBrand();

        return [
            'Forcing links to iPlayer' => [$tvEpisode, true, 'http://localhost/iplayer/episode/p0000002'],
            'Clip links to Find By Pid' => [$clip, false, 'http://localhost/programmes/p0000001'],
            'Brand links to Find By Pid' => [$brand, false, 'http://localhost/programmes/br000001'],
        ];
    }

    /** @expectedException App\Exception\InvalidOptionException
     * @dataProvider validateOptionsProvider
     */
    public function testValidateOptions(array $options): void
    {
        new TitlePresenter(
            $this->createMock(StreamUrlHelper::class),
            $this->mockClip,
            $this->router,
            $this->mockTitleLogicHelper,
            $options
        );
    }

    public function validateOptionsProvider(): array
    {
        return [
            'Non-boolean value for text_colour_on_title_link' => [['text_colour_on_title_link' => null]],
            'Non-core entity and non-null value for context_programme' => [['context_programme' => 1]],
            'Non-integer and non-null value for truncation_length' => [['truncation_length' => 'a']],
        ];
    }
}
