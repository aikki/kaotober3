<?php

namespace App\Command;

use App\Repository\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:randomize-ingredients',
    description: 'Add a short description for your command',
)]
class RandomizeIngredientsCommand extends Command
{
    public function __construct(
        private readonly RecipeRepository $recipeRepository,
        private readonly EntityManagerInterface $entityManager,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $recipes = $this->recipeRepository->findAll();
        foreach ($recipes as $recipe) {
            $ingredients = $recipe->getIngredients();
            shuffle($ingredients);
            $recipe->setIngredients($ingredients);
            $this->entityManager->persist($recipe);
        }
        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
