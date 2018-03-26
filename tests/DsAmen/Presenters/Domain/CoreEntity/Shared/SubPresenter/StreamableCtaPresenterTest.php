<?php
declare(strict_types=1);

namespace Tests\App\DsAmen\Presenters\Domain\CoreEntity\Shared\SubPresenter;

use App\DsAmen\Presenters\Domain\CoreEntity\Shared\SubPresenter\StreamableCtaPresenter;
use App\DsShared\Helpers\StreamUrlHelper;
use BBC\ProgrammesPagesService\Domain\Entity\Clip;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeItem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tests\App\DsAmen\Presenters\Domain\CoreEntity\BaseSubPresenterTest;

class StreamableCtaPresenterTest extends BaseSubPresenterTest
{
    /** @var UrlGeneratorInterface */
    private $router;

    protected function setUp()
    {
        $this->router = $this->createRouter();
    }

    /** @dataProvider getDurationProvider */
    public function testGetDuration(ProgrammeItem $programme, int $expected): void
    {
        $ctaPresenter = new StreamableCtaPresenter($this->createMock(StreamUrlHelper::class), $programme, $this->router);
        $this->assertSame($expected, $ctaPresenter->getDuration());
    }

    public function getDurationProvider(): array
    {
        $tvEpisode = $this->createMockTvEpisode();
        $clip = $this->createMockClip();
        $radioEpisode = $this->createMockRadioEpisode();
        $nullDurationClip = $this->createConfiguredMock(Clip::class, ['getDuration' => null]);

        return [
            "TV episode doesn't show duration" => [$tvEpisode, 0],
            "Clip shows duration" => [$clip, 10],
            "Clip with null duration doesn't show duration" => [$nullDurationClip, 0],
            "Radio episode shows duration" => [$radioEpisode, 30],
        ];
    }

    /** @dataProvider getLinkLocationPrefixProvider */
    public function testGetLinkLocationPrefix(bool $forceIplayerLinking, bool $isTv, string $expected): void
    {
        $mockClip = $this->createMockClip();
        $mockClip->method('isTv')->willReturn($isTv);

        $ctaPresenter = new StreamableCtaPresenter(
            $this->createMock(StreamUrlHelper::class),
            $mockClip,
            $this->router,
            [
                'force_iplayer_linking' => $forceIplayerLinking,
                'link_location_prefix' => 'programmeobject_',
            ]
        );

        $this->assertSame($expected, $ctaPresenter->getLinkLocation());
    }

    public function getLinkLocationPrefixProvider(): array
    {
        return [
            'Forcing iPlayer linking on TV Clip' => [true, true, 'map_iplayer_calltoaction'],
            'Not forcing iPlayer linking on TV Clip' => [false, true, 'programmeobject_calltoaction'],
            'Forcing iPlayer linking on non-TV Clip' => [true, false, 'programmeobject_calltoaction'],
        ];
    }

    /** @dataProvider getMediaIconNameProvider */
    public function testGetMediaIconName(ProgrammeItem $programme, string $expected): void
    {
        $ctaPresenter = new StreamableCtaPresenter($this->createMock(StreamUrlHelper::class), $programme, $this->router);
        $this->assertSame($expected, $ctaPresenter->getMediaIconName());
    }

    public function getMediaIconNameProvider(): array
    {
        $tvEpisode = $this->createMockTvEpisode();
        $clip = $this->createMockClip();
        $radioEpisode = $this->createMockRadioEpisode();

        return [
            'TV episode shows iPlayer CTA icon' => [$tvEpisode, 'iplayer'],
            'Clip shows play CTA icon' => [$clip, 'play'],
            'Radio episode shows iPlayer Radio CTA icon' => [$radioEpisode, 'iplayer-radio'],
        ];
    }


    public function testGetPlayTranslation(): void
    {
        $programme = $this->createMockTvEpisode();
        $ctaPresenter = new StreamableCtaPresenter($this->createMock(StreamUrlHelper::class), $programme, $this->router);
        $this->assertSame('', $ctaPresenter->getLabelTranslation(), 'None streamable CTA presenter has label translations');
    }

    public function testGetUrl(): void
    {
        $episode = $this->createMockTvEpisode();
        $urlHelper = $this->createMock(StreamUrlHelper::class);
        $urlHelper->expects($this->once())->method('getRouteForProgrammeItem')->with($episode)->willReturn('iplayer_play');
        $ctaPresenter = new StreamableCtaPresenter($urlHelper, $episode, $this->router);
        $ctaPresenter->getUrl();
    }
}
