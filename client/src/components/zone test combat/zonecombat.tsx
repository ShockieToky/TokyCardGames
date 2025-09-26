
import React, { useEffect, useState } from 'react';
import '../../styles/choixequipe.css';

// Typages robustes
interface Skill {
    id: number | string;
    name: string;
    cooldown?: number;
    // Ajoute d'autres propriétés utiles
}
interface Hero {
    id: number | string;
    name: string;
    // ...autres propriétés de base
}
interface Fighter extends Hero {
    hp: number;
    maxHp: number;
    alive: boolean;
    team: 'A' | 'B';
    skills: Skill[];
    // ...autres propriétés utiles
}
interface CombatLog {
    message: string;
    timestamp: number;
}
interface CombatState {
    id: string;
    fighters: Fighter[];
    currentTurn: number;
    phase: string;
    logs: CombatLog[];
    winner: string | null;
    // Ajoute ici tout ce que ton backend retourne
}
interface ZoneCombatProps {
    teamA: Hero[];
    teamB: Hero[];
    onBackToSelection: () => void;
}

const ZoneCombat: React.FC<ZoneCombatProps> = ({ teamA, teamB, onBackToSelection }) => {
    const [combatState, setCombatState] = useState<CombatState | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string>('');
    const [selectedSkill, setSelectedSkill] = useState<Skill | null>(null);
    const [selectedTarget, setSelectedTarget] = useState<number | string | null>(null);

    // Initialiser le combat côté backend
    useEffect(() => {
        const startCombat = async () => {
            setLoading(true);
            setError('');
            try {
                const response = await fetch('http://localhost:8000/api/combat/start', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({
                        teamA: teamA.map(h => h.id),
                        teamB: teamB.map(h => h.id)
                    })
                });
                if (response.ok) {
                    const data = await response.json();
                    setCombatState(data);
                } else {
                    setError("Erreur lors de l'initialisation du combat");
                }
            } catch (e) {
                setError('Erreur de connexion au serveur');
            }
            setLoading(false);
        };
        startCombat();
    }, [teamA, teamB]);

    // Gérer l’action utilisateur (attaque, sort, etc.)
    const handleAction = async (skillId: number | string, targetId: number | string) => {
        if (!combatState) return;
        setLoading(true);
        setError('');
        try {
            const response = await fetch('http://localhost:8000/api/combat/action', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({
                    combatId: combatState.id,
                    skillId,
                    targetId
                })
            });
            if (response.ok) {
                const data = await response.json();
                setCombatState(data);
                setSelectedSkill(null);
                setSelectedTarget(null);
            } else {
                setError("Erreur lors de l'action");
            }
        } catch (e) {
            setError('Erreur de connexion au serveur');
        }
        setLoading(false);
    };

    // Affichage
    if (loading) {
        return <div className="zone-combat-container">Chargement du combat...</div>;
    }
    if (error) {
        return <div className="zone-combat-container">{error}</div>;
    }
    if (!combatState) {
        return <div className="zone-combat-container">Aucun état de combat.</div>;
    }

    const currentFighter = combatState.fighters[combatState.currentTurn];
    const isUserTurn = currentFighter && currentFighter.alive; // à adapter selon la logique

    return (
        <div className="zone-combat-container">
            <button onClick={onBackToSelection} className="back-btn">← Retour</button>
            <h1>Combat en cours</h1>
            <div className="battlefield">
                {/* Affiche les fighters, barres de vie, etc. */}
                <div className="fighters-row">
                    {combatState.fighters.map(f => (
                        <div key={f.id} className={`fighter-card ${f.alive ? '' : 'dead'}`}>
                            <div>{f.name}</div>
                            <div>HP: {f.hp} / {f.maxHp}</div>
                            <div>Team: {f.team}</div>
                        </div>
                    ))}
                </div>
            </div>
            <div className="actions-panel">
                {isUserTurn && currentFighter && (
                    <>
                        <div>
                            {/* Affiche les skills du fighter courant */}
                            {currentFighter.skills.map((skill: Skill) => (
                                <button
                                    key={skill.id}
                                    onClick={() => setSelectedSkill(skill)}
                                    disabled={!!skill.cooldown || !currentFighter.alive}
                                >
                                    {skill.name}
                                </button>
                            ))}
                        </div>
                        {selectedSkill && (
                            <div>
                                {/* Affiche les cibles possibles */}
                                {combatState.fighters
                                    .filter(f => f.alive && f.id !== currentFighter.id)
                                    .map(f => (
                                        <button
                                            key={f.id}
                                            onClick={() => handleAction(selectedSkill.id, f.id)}
                                        >
                                            {f.name}
                                        </button>
                                    ))}
                            </div>
                        )}
                    </>
                )}
            </div>
            <div className="combat-log">
                <h3>Journal</h3>
                <div>
                    {combatState.logs.slice(-5).map((log, i) => (
                        <div key={i}>{log.message}</div>
                    ))}
                </div>
            </div>
        </div>
    );
};

export default ZoneCombat;