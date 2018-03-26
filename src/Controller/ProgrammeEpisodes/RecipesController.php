<?php
declare(strict_types = 1);

namespace App\Controller\ProgrammeEpisodes;

use App\ExternalApi\Recipes\Domain\RecipesApiResult;
use App\ExternalApi\Recipes\Service\RecipesService;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;

class RecipesController extends BaseProgrammeEpisodesController
{
    public function __invoke(
        RecipesService $recipesService,
        Episode $episode
    ) {
        /** @var RecipesApiResult $recipes */
        $recipes = $recipesService->fetchRecipesByPid((string) $episode->getPid())->wait();

        return $this->renderWithChrome('programme_episodes/player.html.twig', [
            'recipes' => $recipes,
        ]);
    }
}
