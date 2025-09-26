import React, { useEffect, useState } from 'react';
import '../../styles/zonecombat.css';

// Typages robustes
interface Skill {
    id: number | string;
    name: string;
    cooldown?: number;
    is_passive?: boolean;
    available?: boolean;
    description?: string;
}

interface Hero {
    id: number | string;
    name: string;
    hp: number;
    attack: number;
    defense: number;
    speed: number;
    resistance: number;
    star: number;
    type: number;
}

interface Fighter extends Hero {
    hp: number;
    maxHp: number;
    alive: boolean; // Changé de 'isAlive' à 'alive' pour correspondre au backend
    team: 'A' | 'B';
    skills: Skill[];
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

    // Initialiser le combat côté backend
    useEffect(() => {
        const startCombat = async () => {
            console.log('Initialisation du combat avec:', { teamA, teamB });
            setLoading(true);
            setError('');
            try {
                const response = await fetch('http://localhost:8000/combat/start', {
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
                    console.log('Combat initialisé:', data);
                    setCombatState(data);
                } else {
                    const errorText = await response.text();
                    console.error('Erreur HTTP:', response.status, errorText);
                    setError(`Erreur lors de l'initialisation du combat: ${response.status}`);
                }
            } catch (e) {
                console.error('Erreur de connexion:', e);
                setError('Erreur de connexion au serveur');
            }
            setLoading(false);
        };

        startCombat();
    }, [teamA, teamB]);

    // Gérer l'action utilisateur (attaque, sort, etc.)
    const handleAction = async (skillId: number | string, targetId: number | string) => {
        if (!combatState) return;
        setLoading(true);
        setError('');
        try {
            const response = await fetch('http://localhost:8000/combat/action', {
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
                console.log('Action exécutée, nouvel état:', data);
                setCombatState(data);
                setSelectedSkill(null);
            } else {
                const errorText = await response.text();
                console.error('Erreur action:', errorText);
                setError("Erreur lors de l'action");
            }
        } catch (e) {
            console.error('Erreur connexion action:', e);
            setError('Erreur de connexion au serveur');
        }
        setLoading(false);
    };

    // Affichage
    if (loading) {
        return (
            <div className="zone-combat-container">
                <div className="loading-message">
                    ⏳ Chargement du combat...
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="zone-combat-container">
                <div className="error-message">
                    ❌ {error}
                    <button onClick={onBackToSelection} className="back-btn">
                        ← Retour à la sélection
                    </button>
                </div>
            </div>
        );
    }

    if (!combatState) {
        return (
            <div className="zone-combat-container">
                <div className="error-message">
                    ❌ Aucun état de combat disponible
                    <button onClick={onBackToSelection} className="back-btn">
                        ← Retour à la sélection
                    </button>
                </div>
            </div>
        );
    }

    // Trouver le combattant dont c'est le tour
    const currentFighter = combatState.fighters[combatState.currentTurn];
    const isUserTurn = currentFighter && currentFighter.alive;

    // Vérifier si le combat est terminé
    if (combatState.winner) {
        return (
            <div className="zone-combat-container">
                <div className="combat-header">
                    <h1>🏆 Combat terminé !</h1>
                    <h2>L'équipe {combatState.winner} a gagné !</h2>
                    <button onClick={onBackToSelection} className="back-btn">
                        ← Retour à la sélection
                    </button>
                </div>
                <div className="combat-log">
                    <h3>📜 Journal du Combat</h3>
                    <div className="log-container">
                        {combatState.logs.slice(-10).map((log, i) => (
                            <div key={i} className="log-entry">
                                {log.message}
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="zone-combat-container">
            <div className="combat-header">
                <button onClick={onBackToSelection} className="back-btn">
                    ← Retour à la sélection
                </button>
                <h1>⚔️ Combat en cours</h1>
                <div className="combat-info">
                    Phase: {combatState.phase} | Tour de: {currentFighter?.name || 'En attente'}
                </div>
            </div>

            <div className="battlefield">
                <div className="fighters-display">
                    <div className="team-section">
                        <h2>🔵 Équipe A</h2>
                        <div className="fighters-row">
                            {combatState.fighters
                                .filter(f => f.team === 'A')
                                .map(f => (
                                    <div key={f.id} className={`fighter-card ${f.alive ? '' : 'dead'} ${currentFighter?.id === f.id ? 'current-turn' : ''}`}>
                                        <div className="fighter-name">{f.name}</div>
                                        <div className="hp-bar-container">
                                            <div className="hp-bar">
                                                <div
                                                    className="hp-fill"
                                                    style={{ width: `${(f.hp / f.maxHp) * 100}%` }}
                                                ></div>
                                            </div>
                                            <div className="hp-text">
                                                {f.hp}/{f.maxHp} PV - {f.alive ? 'En vie' : 'KO'}
                                            </div>
                                        </div>
                                        {currentFighter?.id === f.id && (
                                            <div className="turn-marker">🎯 Votre tour</div>
                                        )}
                                    </div>
                                ))}
                        </div>
                    </div>

                    <div className="vs-divider">VS</div>

                    <div className="team-section">
                        <h2>🔴 Équipe B</h2>
                        <div className="fighters-row">
                            {combatState.fighters
                                .filter(f => f.team === 'B')
                                .map(f => (
                                    <div key={f.id} className={`fighter-card ${f.alive ? '' : 'dead'} ${currentFighter?.id === f.id ? 'current-turn' : ''}`}>
                                        <div className="fighter-name">{f.name}</div>
                                        <div className="hp-bar-container">
                                            <div className="hp-bar">
                                                <div
                                                    className="hp-fill"
                                                    style={{ width: `${(f.hp / f.maxHp) * 100}%` }}
                                                ></div>
                                            </div>
                                            <div className="hp-text">
                                                {f.hp}/{f.maxHp} PV - {f.alive ? 'En vie' : 'KO'}
                                            </div>
                                        </div>
                                        {currentFighter?.id === f.id && (
                                            <div className="turn-marker">🎯 Votre tour</div>
                                        )}
                                    </div>
                                ))}
                        </div>
                    </div>
                </div>
            </div>

            {isUserTurn && currentFighter && (
                <div className="actions-panel">
                    <h3>🎮 Actions pour {currentFighter.name}</h3>
                    <div className="skills-section">
                        <h4>Choisir une compétence :</h4>
                        <div className="skills-grid">
                            {currentFighter.skills.map((skill: Skill) => (
                                <button
                                    key={skill.id}
                                    onClick={() => skill.available && !skill.is_passive ? setSelectedSkill(skill) : null}
                                    disabled={!skill.available || skill.is_passive || !currentFighter.alive}
                                    className={`skill-btn ${selectedSkill?.id === skill.id ? 'selected' : ''} ${!skill.available || skill.is_passive ? 'disabled' : ''
                                        } ${skill.cooldown && skill.cooldown > 0 ? 'on-cooldown' : ''} ${skill.is_passive ? 'passive-skill' : ''
                                        }`}
                                    title={skill.is_passive ? 'Compétence passive - ne peut pas être lancée' : skill.description}
                                >
                                    {skill.name}
                                    {skill.is_passive && <span className="passive-indicator"> [PASSIF]</span>}
                                    {skill.cooldown && skill.cooldown > 0 && (
                                        <span className="cooldown"> ({skill.cooldown} tours)</span>
                                    )}
                                </button>
                            ))}
                        </div>
                    </div>

                    {selectedSkill && !selectedSkill.is_passive && (
                        <div className="targets-section">
                            <h4>Choisir une cible pour : {selectedSkill.name}</h4>
                            <div className="targets-grid">
                                {combatState.fighters
                                    .filter(f => f.alive && f.id !== currentFighter.id)
                                    .map(f => (
                                        <button
                                            key={f.id}
                                            onClick={() => handleAction(selectedSkill.id, f.id)}
                                            className={`target-btn team-${f.team.toLowerCase()}`}
                                        >
                                            {f.name} (Équipe {f.team})
                                            <br />
                                            <small>HP: {f.hp}/{f.maxHp}</small>
                                        </button>
                                    ))}
                            </div>
                        </div>
                    )}
                </div>
            )}

            <div className="combat-log">
                <h3>📜 Journal du Combat</h3>
                <div className="log-container">
                    {combatState.logs.slice(-5).map((log, i) => (
                        <div key={i} className="log-entry">
                            {log.message}
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
};

export default ZoneCombat;