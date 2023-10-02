<?php

namespace App\Enum;

enum GameState: int
{
    case IN_PROGRESS = 0;
    case WIN = 1;
    case LOSE = -1;
}
