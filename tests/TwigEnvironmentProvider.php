<?php
declare(strict_types = 1);
namespace Tests\App;

use App\DsShared\Helpers\HelperFactory;
use App\Ds2013\PresenterFactory as Ds2013PresenterFactory;
use App\DsAmen\PresenterFactory as DsAmenPresenterFactory;
use App\Translate\TranslateProvider;
use App\Twig\DesignSystemPresenterExtension;
use App\Twig\GelIconExtension;
use App\Twig\HtmlUtilitiesExtension;
use App\Twig\RdfaSchemaExtension;
use App\Twig\TranslateAndTimeExtension;
use RMP\Translate\TranslateFactory;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Twig_Environment;
use Twig_Loader_Filesystem;

class TwigEnvironmentProvider
{
    /** @var Twig_Environment */
    static private $twig;

    /** @var PresenterFactory */
    static private $ds2013PresenterFactory;

    static private $dsAmenPresenterFactory;

    public static function twig(): Twig_Environment
    {
        if (self::$twig === null) {
            self::build();
        }

        return self::$twig;
    }

    public static function ds2013PresenterFactory(): Ds2013PresenterFactory
    {
        if (self::$ds2013PresenterFactory === null) {
            self::build();
        }

        return self::$ds2013PresenterFactory;
    }

    public static function dsAmenPresenterFactory(): DsAmenPresenterFactory
    {
        if (self::$dsAmenPresenterFactory === null) {
            self::build();
        }

        return self::$dsAmenPresenterFactory;
    }

    private static function build(): void
    {
        $loader = new Twig_Loader_Filesystem(__DIR__ . '/../app/Resources');
        $loader->addPath(__DIR__ . '/../src/Ds2013', 'Ds2013');

        $twig = new Twig_Environment($loader, ['strict_variables' => true]);

        $translateFactory = new TranslateFactory([
            'fallback_locale' => 'en_GB',
            'cachepath' => __DIR__ . '/../tmp/cache/test/translations',
            'domains' => ['programmes'],
            'default_domain' => 'programmes',
            'debug' => true,
            'basepath' => __DIR__ . '/../app/Resources/translations',
        ]);

        $translateProvider = new TranslateProvider($translateFactory);

        $assetPackages = new Packages(new Package(new EmptyVersionStrategy()));

        $routeCollectionBuilder = new RouteCollectionBuilder(new YamlFileLoader(
            new FileLocator([__DIR__ . '/../app/config'])
        ));
        $routeCollectionBuilder->import('routing.yml');

        // Symfony extensions

        $twig->addExtension(new AssetExtension($assetPackages));

        $router = new UrlGenerator(
            $routeCollectionBuilder->build(),
            new RequestContext()
        );
        $twig->addExtension(new RoutingExtension($router));

        // Programmes extensions
        $helperFactory = new HelperFactory($translateProvider, $router);

        // Set presenter factory for template tests to use.
        self::$ds2013PresenterFactory = new Ds2013PresenterFactory($translateProvider, $router, $helperFactory);
        self::$dsAmenPresenterFactory = new DsAmenPresenterFactory($translateProvider, $router, $helperFactory);

        $twig->addExtension(new DesignSystemPresenterExtension(
            self::$ds2013PresenterFactory,
            self::$dsAmenPresenterFactory
        ));

        $twig->addExtension(new TranslateAndTimeExtension($translateProvider));

        $twig->addExtension(new GelIconExtension());

        $twig->addExtension(new HtmlUtilitiesExtension($assetPackages));

        $twig->addExtension(new RdfaSchemaExtension($assetPackages));

        // Set twig for template tests to use
        self::$twig = $twig;
    }
}
