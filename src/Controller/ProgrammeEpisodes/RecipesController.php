<?php
declare(strict_types = 1);

namespace App\Controller\ProgrammeEpisodes;

use App\ExternalApi\Recipes\Domain\RecipesApiResult;
use App\ExternalApi\Recipes\Service\RecipesService;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeContainer;

class RecipesController extends BaseProgrammeEpisodesController
{
    public function __invoke(
        RecipesService $recipesService,
        Episode $programme
    ) {
        /** @var RecipesApiResult $recipes */
        $recipes = $recipesService->fetchRecipesByPid((string) $programme->getPid())->wait();

        return $this->renderWithChrome('programme_episodes/recipes.html.twig', [
            'recipes' => $recipes,
        ]);
    }
}
