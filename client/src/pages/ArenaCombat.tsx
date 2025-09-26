import React, { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import Background from "../components/background";
import ZoneCombat from "../components/zone test combat/zonecombat";

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

const ArenaCombat = () => {
    const navigate = useNavigate();
    const [combatTeams, setCombatTeams] = useState<{ teamA: Hero[], teamB: Hero[] } | null>(null);

    useEffect(() => {
        // Récupérer les équipes depuis le sessionStorage
        const storedTeams = sessionStorage.getItem('combatTeams');

        if (storedTeams) {
            try {
                const teams = JSON.parse(storedTeams);
                setCombatTeams(teams);
                console.log('Équipes récupérées:', teams);
            } catch (error) {
                console.error('Erreur lors du parsing des équipes:', error);
                navigate('/test-combat'); // Retour à la sélection si erreur
            }
        } else {
            console.warn('Aucune équipe trouvée, redirection vers la sélection');
            navigate('/test-combat'); // Retour à la sélection si pas d'équipes
        }
    }, [navigate]);

    const handleBackToSelection = () => {
        // Nettoyer le sessionStorage
        sessionStorage.removeItem('combatTeams');
        // Retourner à la page de sélection
        navigate('/test-combat');
    };

    // Affichage de chargement pendant la récupération des équipes
    if (!combatTeams) {
        return (
            <div>
                <Background />
                <div style={{
                    display: 'flex',
                    justifyContent: 'center',
                    alignItems: 'center',
                    height: '100vh',
                    color: 'white',
                    fontSize: '1.5rem'
                }}>
                    ⏳ Préparation du combat...
                </div>
            </div>
        );
    }

    return (
        <div>
            <Background />
            <ZoneCombat
                teamA={combatTeams.teamA}
                teamB={combatTeams.teamB}
                onBackToSelection={handleBackToSelection}
            />
        </div>
    );
};

export default ArenaCombat;