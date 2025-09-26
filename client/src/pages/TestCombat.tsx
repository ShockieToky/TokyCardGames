import React, { useState } from "react";
import { useNavigate } from "react-router-dom";
import Background from "../components/background";
import ChoixEquipe from "../components/zone test combat/choixequipe";

interface Hero {
    id: number;
    name: string;
    hp: number;
    attack: number;
    defense: number;
    speed: number;
    resistance: number;
    star: number;
    type: number;
}

const TestCombat = () => {
    const navigate = useNavigate();

    const handleStartCombat = (teamA: Hero[], teamB: Hero[]) => {
        console.log('Démarrage du combat avec les équipes:', { teamA, teamB });

        // Stocker les équipes dans le sessionStorage pour les passer à la page de combat
        sessionStorage.setItem('combatTeams', JSON.stringify({
            teamA,
            teamB
        }));

        // Naviguer vers la page de combat
        navigate('/arena-combat');
    };

    return (
        <div>
            <Background />
            <ChoixEquipe onStartCombat={handleStartCombat} />
        </div>
    );
};

export default TestCombat;