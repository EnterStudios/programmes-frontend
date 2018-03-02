<?php

namespace Tests\App\Controller\ProgrammeEpisodes;

use Symfony\Bundle\FrameworkBundle\Client;
use Tests\App\BaseWebTestCase;
use Tests\App\Controller\ProgrammeEpisodes\DomParsers\EpisodesGuideDomParser;

class GuideControllerTest extends BaseWebTestCase
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

    public function testIndexControllerCanLoadGuideItems()
    {
        $crawler = $this->client->request('GET', '/programmes/b006q2x0/episodes/guide');

        $this->assertResponseStatusCode($this->client, 200);
        // assert type of template
        $this->assertEquals(1, $crawler->filter('.footer')->count(), 'NON Partial-templates should include a footer');
        // assert guide items
        $this->assertEquals(3, $crawler->filter('.js-guideitem')->count());
        $this->assertEquals(2, $crawler->filter('.episode-guide__series-container')->count());
        $this->assertEquals(1, $crawler->filter('.js-guideitem .programme--episode')->count());
        // assert guide items order
        $this->assertEquals(['B1-S2', 'B1-S1', 'B1-E1'], $this->findAllTitles($crawler));
    }

    public function testIndexControllerCannotDisplayAnyPageWhenDoesntExist()
    {
        $this->client->request('GET', '/programmes/b006q2x0/episodes/guide?page=2');

        $this->assertResponseStatusCode($this->client, 404);
    }

    private function findAllTitles($crawler)
    {
        $titles = $crawler->filter('ol li a .link-complex__target');

        $titlesText = [];
        foreach ($titles as $title) {
            $titlesText[] = $title->textContent;
        }

        $titles = $crawler->filter('li h2 a')->filter('span.programme__title')->eq(0);
        foreach ($titles as $title) {
            $titlesText[] = $title->textContent;
        }

        return $titlesText;
    }
}
