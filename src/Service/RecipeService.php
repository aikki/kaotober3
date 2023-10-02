<?php

namespace App\Service;

use App\Entity\Recipe;
use App\Repository\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;

class RecipeService
{
    public const LIVES = 5;

    public function __construct(
        private readonly RecipeRepository $recipeRepository,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    public function getTodayRecipe(): Recipe
    {
        $recipe = $this->recipeRepository->findOneBy(['date' => new \DateTime()]);
        if (!$recipe instanceof Recipe) {
            $freeRecipes = $this->recipeRepository->findBy(['date' => null]);
            if (empty($freeRecipes)) {
                $freeRecipes = $this->recipeRepository->findAll();
            }
            $recipe = $freeRecipes[array_rand($freeRecipes)]->setDate(new \DateTime());
            $this->entityManager->persist($recipe);
            $this->entityManager->flush();
        }
        return $recipe;
    }

    public function getRandomRecipe(): Recipe
    {
        $recipes = $this->recipeRepository->findAll();
        return $recipes[array_rand($recipes)];
    }

    public function getIngredientsMap(Recipe $recipe, $lives = self::LIVES): array
    {
        $map = array_fill(0, $lives, 1);
        $remaining = count($recipe->getIngredients()) - $lives;
        $cur = $lives;
        for ($i = 0; $i < $remaining; $i++) {
            $map[--$cur]++;
            if ($cur <= 1) {
                $cur = $lives;
            }
        }
        return $map;
    }
}