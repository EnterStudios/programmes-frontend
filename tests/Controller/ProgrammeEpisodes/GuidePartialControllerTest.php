<?php
declare(strict_types = 1);
namespace Tests\App\Controller\ProgrammeEpisodes;

use Symfony\Bundle\FrameworkBundle\Client;
use Tests\App\BaseWebTestCase;
use Tests\App\Controller\ProgrammeEpisodes\DomParsers\EpisodesGuideDomParser;

class GuidePartialControllerTest extends BaseWebTestCase
{
    /** @var Client */
    protected $client;

    public function setUp()
    {
        $this->loadFixtures([
            'ProgrammeEpisodes\EpisodesFixtures',
        ]);

        $this->client = static::createClient();
    }

    public function testBasicScenarioOfEpisodeGuideWithPartial()
    {
        $crawler = $this->client->request('GET', '/programmes/b006q2x0/episodes/guide.2013inc');

        $this->assertResponseStatusCode($this->client, 200);
        // assert guide items
        $this->assertEquals(0, $crawler->filter('.footer')->count(), 'Partial-templates shouldnt have any footer');
        $this->assertEquals(3, $crawler->filter('.js-guideitem')->count());
        $this->assertEquals(2, $crawler->filter('.episode-guide__series-container')->count());
        $this->assertEquals(1, $crawler->filter('.js-guideitem .programme--episode')->count());
        // assert guide items order
        $this->assertEquals(['B1-S2', 'B1-S1', 'B1-E1'], $this->findAllTitles($crawler));
    }

    public function testEpisodeGuideWithThreeNestedLevelsAndPartial()
    {

        $crawler = $this->client->request('GET', '/programmes/b006q2x0/episodes/guide.2013inc?nestedlevel=3');

        $this->assertResponseStatusCode($this->client, 200);
        // assert temlate type
        $this->assertEquals(0, $crawler->filter('.footer')->count(), 'Partial-templates shouldnt have any footer');
        // assert guide items
        $this->assertEquals(3, $crawler->filter('.js-guideitem')->count());
        $this->assertEquals(2, $crawler->filter('.episode-guide__series-container')->count());
        $this->assertEquals(1, $crawler->filter('.js-guideitem .programme--episode')->count());
        // assert guide items order
        $this->assertEquals(['B1-S2', 'B1-S1', 'B1-E1'], $this->findAllTitles($crawler));
        // assert headings
        $this->assertTrue($crawler->filter('h3')->count() > 0);
        $this->assertEquals(0, $crawler->filter('h2')->count());
        $this->assertEquals(0, $crawler->filter('h1')->count());
    }

    public function testEpisodeGuideWithOneNestedLevelsAndPartial()
    {

        $crawler = $this->client->request('GET', '/programmes/b006q2x0/episodes/guide.2013inc?nestedlevel=1');

        $this->assertResponseStatusCode($this->client, 200);
        // assert temlate type
        $this->assertEquals(0, $crawler->filter('.footer')->count(), 'Partial-templates should have any footer');
        // assert guide items
        $this->assertEquals(3, $crawler->filter('.js-guideitem')->count());
        $this->assertEquals(2, $crawler->filter('.episode-guide__series-container')->count());
        $this->assertEquals(1, $crawler->filter('.js-guideitem .programme--episode')->count());
        // assert guide items order
        $this->assertEquals(['B1-S2', 'B1-S1', 'B1-E1'], $this->findAllTitles($crawler));
        // assert headings
        $this->assertEquals(0, $crawler->filter('h3')->count());
        $this->assertEquals(0, $crawler->filter('h2')->count());
        $this->assertTrue($crawler->filter('h1')->count() > 0);
    }

    private function findAllTitles($crawler)
    {
        $titles = $crawler->filter('ol li a .link-complex__target');

        $titlesText = [];
        foreach ($titles as $title) {
            $titlesText[] = $title->textContent;
        }

        $titles = $crawler->filter('li a')->filter('span.programme__title')->eq(0);
        foreach ($titles as $title) {
            $titlesText[] = $title->textContent;
        }

        return $titlesText;
    }
}
