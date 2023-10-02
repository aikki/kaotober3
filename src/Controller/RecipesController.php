<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Recipe;
use App\Enum\GameState;
use App\Repository\RecipeRepository;
use App\Service\RecipeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RecipesController extends AbstractController
{
    public function __construct(
        private readonly RecipeRepository $recipeRepository,
        private readonly RecipeService $recipeService,
    )
    {
    }

    #[Route('/', name: 'app_index')]
    public function index(Request $request): Response
    {
        $recipe = $this->recipeService->getTodayRecipe();

        $session = $request->getSession();

        $game = $session->get('daily');

        if (!$game instanceof Game || $game->date != (new \DateTimeImmutable())->modify('today')) {
            $game = new Game();
            $game->recipeId = $recipe->getId();
            $session->set('daily', $game);
        }

        return $this->handleGame($game, $recipe, $request, 'recipes/index.html.twig');
    }

    #[Route('/random', name: 'app_random')]
    public function random(Request $request): Response
    {
        $session = $request->getSession();

        $game = $session->get('random');

        if (!$game instanceof Game || $game->date != (new \DateTimeImmutable())->modify('today')) {
            $recipe = $this->recipeService->getRandomRecipe();
            $game = new Game();
            $game->recipeId = $recipe->getId();
            $session->set('random', $game);
        } else {
            $recipe = $this->recipeRepository->find($game->recipeId);
        }

        return $this->handleGame($game, $recipe, $request, 'recipes/index.html.twig', 'random', 'app_random');
    }

    #[Route('/random/reset', name: 'app_random_reset')]
    public function reset(Request $request): Response
    {
        $session = $request->getSession();

        $session->remove('random');

        return $this->redirectToRoute('app_random');
    }

    private function handleGame(Game $game, Recipe $recipe, Request $request, string $view, string $mode = 'daily', $route = 'app_index'): Response
    {
        $ingredientsMap = $this->recipeService->getIngredientsMap($recipe);

        $recipes = implode(',', array_map(function ($e) {
            return '"' . $e->getName() . '"';
        }, $this->recipeRepository->findAll()));

        $form = $this->createFormBuilder()
            ->add('guess', null, [
                'attr' => [
                    'autofocus' => true,
                ]
            ])
            ->getForm();

        $showIngredients = 0;

        if ($game->life < RecipeService::LIVES && $game->state === GameState::IN_PROGRESS) {

            for ($i = 0; $i <= $game->life; $i++) {
                $showIngredients += $ingredientsMap[$i];
            }

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $guess = $form->get('guess')->getData();
                if ($guess !== $recipe->getName()) {
                    $game->lives[$game->life] = false;
                    $game->life++;
                    if ($game->life >= RecipeService::LIVES) {
                        $game->state = GameState::LOSE;
                    }
                } else {
                    $game->lives[$game->life] = true;
                    $game->state = GameState::WIN;
                }
                $request->getSession()->set('daily', $game);
                return $this->redirectToRoute($route);
            }
        } else {
            $showIngredients = count($recipe->getIngredients());
        }


        return $this->render($view, [
            'recipe' => $recipe,
            'recipes' => $recipes,
            'lives' => $game->lives,
            'form' => $form,
            'show' => $showIngredients,
            'win' => $game->state->value,
            'date' => $game->date,
            'mode' => $mode,
        ]);
    }


//    #[Route('/recipes/add', name: 'app_recipes')]
    public function addRecipe(Request $request, EntityManagerInterface $entityManager): Response
    {
        $recipe = new Recipe();

        $form = $this->createFormBuilder($recipe)
            ->add('name')
            ->add('ingredientsText', TextareaType::class, [
                'mapped' => false
            ])
            ->add('submit', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ingredients = array_map(
                function ($e) {
                    return trim($e);
                },
                preg_split("/\r\n|\n|\r/", $form->get('ingredientsText')->getData()));
            $recipe->setIngredients($ingredients);
            $entityManager->persist($recipe);
            $entityManager->flush();
            return $this->redirectToRoute('app_recipes');
        }

        return $this->render('recipes/add.html.twig', [
            'form' => $form,
        ]);
    }
}
