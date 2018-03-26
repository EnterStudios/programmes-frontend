<?php

namespace App\DsShared\Helpers;

use App\Translate\TranslateProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HelperFactory
{
    /** @var TranslateProvider */
    private $translateProvider;

    /** @var UrlGeneratorInterface */
    private $router;

    /** @var array */
    private $helpers = [];

    /**
     * This helper factory caches all created objects. DO NOT CREATE HELPERS THAT REQUIRE DATA TO BE CONSTRUCTED.
     * Services are fine, but not per-page data.
     * All functions in this factory should take no parameters. Think of these as a less braindead version
     * of PHP traits.
     */
    public function __construct(TranslateProvider $translateProvider, UrlGeneratorInterface $router)
    {
        $this->translateProvider = $translateProvider;
        $this->router = $router;
    }

    public function getBroadcastNetworksHelper()
    {
        return $this->getHelper(BroadcastNetworksHelper::class, $this->translateProvider);
    }

    public function getLiveBroadcastHelper(): LiveBroadcastHelper
    {
        return $this->getHelper(LiveBroadcastHelper::class, $this->router);
    }

    public function getLocalisedDaysAndMonthsHelper(): LocalisedDaysAndMonthsHelper
    {
        return $this->getHelper(LocalisedDaysAndMonthsHelper::class, $this->translateProvider);
    }

    public function getPlayTranslationsHelper(): PlayTranslationsHelper
    {
        return $this->getHelper(PlayTranslationsHelper::class, $this->translateProvider);
    }

    public function getStreamUrlHelper(): StreamUrlHelper
    {
        return $this->getHelper(StreamUrlHelper::class);
    }

    public function getTitleLogicHelper(): TitleLogicHelper
    {
        return $this->getHelper(TitleLogicHelper::class);
    }

    private function getHelper(string $className, ...$injectables)
    {
        if (!isset($this->helpers[$className])) {
            $this->helpers[$className] = new $className(...$injectables);
        }
        return $this->helpers[$className];
    }
}
