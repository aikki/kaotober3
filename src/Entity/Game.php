<?php

namespace App\Entity;

use App\Enum\GameState;
use App\Service\RecipeService;

class Game
{
    public int $recipeId;
    public int $life;
    public array $lives;
    public GameState $state;
    public \DateTimeImmutable $date;

    public function __construct()
    {
        $this->life = 0;
        $this->lives = array_fill(0, RecipeService::LIVES, null);
        $this->state = GameState::IN_PROGRESS;
        $this->date = new \DateTimeImmutable('today');
    }
}