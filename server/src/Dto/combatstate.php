<?php

class combatState
{
    public int $id;
    public string $fighterId;
    public int $fighters;
    public int $turn;
    public int $hasplayed;
    public int $currentTurnOrder;
    public array $log;
    public int $round;
    public string $phase;
    public ?int $winnerTeam;

    public static function combatState (
        int $id,
        string $fighterId,
        int $fighters,
        int $turn,
        int $hasplayed,
        int $currentTurnOrder,
        array $log,
        int $round,
        string $phase,
        ?int $winnerTeam)
        : self {
        $combatState = new self();
        $combatState->id = $id;
        $combatState->fighterId = $fighterId;
        $combatState->fighters = $fighters;
        $combatState->turn = $turn;
        $combatState->hasplayed = $hasplayed;
        $combatState->currentTurnOrder = $currentTurnOrder;
        $combatState->log = $log;
        $combatState->round = $round;
        $combatState->phase = $phase;
        $combatState->winnerTeam = $winnerTeam;
        return $combatState;
    }
}