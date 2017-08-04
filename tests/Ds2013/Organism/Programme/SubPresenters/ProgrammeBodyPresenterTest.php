<?php
declare(strict_types = 1);

namespace Tests\App\Ds2013\Organism\Programme\SubPresenters;

use App\DsShared\Helpers\PlayTranslationsHelper;
use App\Ds2013\Organism\Programme\SubPresenters\ProgrammeBodyPresenter;
use BBC\ProgrammesPagesService\Domain\Entity\Brand;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProgrammeBodyPresenterTest extends TestCase
{
    private $mockRouter;

    private $mockTranslationsHelper;

    public function setUp()
    {
        $this->mockRouter = $this->createMock(UrlGeneratorInterface::class);
        $this->mockTranslationsHelper = $this->createMock(PlayTranslationsHelper::class);
    }

    public function testHasDefinedPositionUnderParentProgramme()
    {
        $programme = $this->createMock(Episode::class);
        $parentProgramme = $this->createMock(Brand::class);
        $programme->method('getParent')->willReturn($parentProgramme);
        $programme->method('getPosition')->willReturn(3);
        $parentProgramme->method('getExpectedChildCount')->willReturn(7);

        $programmePresenter = new ProgrammeBodyPresenter(
            $this->mockRouter,
            $this->mockTranslationsHelper,
            $programme
        );
        $this->assertTrue($programmePresenter->hasDefinedPositionUnderParentProgramme());
    }

    public function testDoesNotHaveDefinedPositionUnderParentProgramme()
    {
        $programme = $this->createMock(Episode::class);
        $parentProgramme = $this->createMock(Brand::class);
        $programme->method('getParent')->willReturn($parentProgramme);
        $programme->method('getPosition')->willReturn(3);
        $parentProgramme->method('getExpectedChildCount')->willReturn(null);

        $programmePresenter = new ProgrammeBodyPresenter(
            $this->mockRouter,
            $this->mockTranslationsHelper,
            $programme
        );
        $this->assertFalse($programmePresenter->hasDefinedPositionUnderParentProgramme());
    }

    public function testShouldShowDuration()
    {
        $programme = $this->createMock(Episode::class);
        $programme->expects($this->once())->method('getDuration')->willReturn(3600);

        $programmePresenter = new ProgrammeBodyPresenter(
            $this->mockRouter,
            $this->mockTranslationsHelper,
            $programme,
            ['show_duration' => true]
        );
        $this->assertTrue($programmePresenter->shouldShowDuration());
    }

    public function testShouldNotShowDuration()
    {
        $programme = $this->createMock(Episode::class);

        $programmePresenter = new ProgrammeBodyPresenter(
            $this->mockRouter,
            $this->mockTranslationsHelper,
            $programme,
            ['show_duration' => false]
        );
        $this->assertFalse($programmePresenter->shouldShowDuration());
    }
}
