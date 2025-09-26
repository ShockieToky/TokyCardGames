import React, { useState, useEffect } from 'react';
import '../../styles/zonecombat.css';

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

interface ChoixEquipeProps {
    onStartCombat?: (teamA: Hero[], teamB: Hero[]) => void;
}

const ChoixEquipe: React.FC<ChoixEquipeProps> = ({ onStartCombat }) => {
    const [availableHeroes, setAvailableHeroes] = useState<Hero[]>([]);
    const [teamA, setTeamA] = useState<Hero[]>([]);
    const [teamB, setTeamB] = useState<Hero[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string>('');

    // Charger la liste des héros disponibles
    useEffect(() => {
        fetchHeroes();
    }, []);

    const fetchHeroes = async () => {
        try {
            setLoading(true);
            // CORRECTION : URL avec /api/
            const response = await fetch('http://localhost:8000/heroes');
            if (response.ok) {
                const heroes = await response.json();
                console.log('Héros chargés:', heroes);
                setAvailableHeroes(heroes);
            } else {
                setError('Impossible de charger les héros');
                console.error('Erreur HTTP:', response.status);
            }
        } catch (err) {
            setError('Erreur de connexion');
            console.error('Erreur de fetch:', err);
        } finally {
            setLoading(false);
        }
    };

    const addHeroToTeam = (hero: Hero, team: 'A' | 'B') => {
        if (team === 'A') {
            if (teamA.length < 4 && !teamA.find(h => h.id === hero.id)) {
                setTeamA([...teamA, hero]);
            }
        } else {
            if (teamB.length < 4 && !teamB.find(h => h.id === hero.id)) {
                setTeamB([...teamB, hero]);
            }
        }
    };

    const removeHeroFromTeam = (heroId: number, team: 'A' | 'B') => {
        if (team === 'A') {
            setTeamA(teamA.filter(h => h.id !== heroId));
        } else {
            setTeamB(teamB.filter(h => h.id !== heroId));
        }
    };

    const handleStartCombat = () => {
        if (teamA.length !== 4 || teamB.length !== 4) {
            setError('Chaque équipe doit avoir exactement 4 héros');
            return;
        }

        console.log('Équipes sélectionnées:', { teamA, teamB });

        // Effacer les erreurs
        setError('');

        // Appeler la fonction de callback pour démarrer le combat
        if (onStartCombat) {
            onStartCombat(teamA, teamB);
        }
    };

    const resetTeams = () => {
        setTeamA([]);
        setTeamB([]);
        setError('');
    };

    const isHeroSelected = (heroId: number): string | false => {
        const inTeamA = teamA.find(h => h.id === heroId);
        const inTeamB = teamB.find(h => h.id === heroId);

        if (inTeamA && inTeamB) return 'both';
        if (inTeamA) return 'A';
        if (inTeamB) return 'B';
        return false;
    };

    const getStarDisplay = (stars: number) => {
        const validStars = Math.max(0, Math.min(5, stars || 0));
        return '★'.repeat(validStars) + '☆'.repeat(5 - validStars);
    };

    const getTypeColor = (type: number) => {
        const colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#ffeaa7'];
        return colors[type - 1] || '#ddd';
    };

    const isTeamsReady = teamA.length === 4 && teamB.length === 4;

    if (loading) {
        return (
            <div className="choix-equipe-container">
                <div className="loading-message">
                    ⏳ Chargement des héros...
                </div>
            </div>
        );
    }

    return (
        <div className="choix-equipe-container">
            <div className="header">
                <h1>⚔️ Configuration du Combat ⚔️</h1>
                <button onClick={resetTeams} className="reset-btn">
                    🔄 Réinitialiser
                </button>
            </div>

            {error && (
                <div className="error-message">
                    ❌ {error}
                </div>
            )}

            {isTeamsReady && (
                <div className="teams-ready-message">
                    ✅ Les équipes sont prêtes ! Vous pouvez lancer le combat.
                </div>
            )}

            <div className="teams-container">
                {/* Équipe A */}
                <div className="team-section">
                    <h2 className="team-title team-a">🔵 Équipe A ({teamA.length}/4)</h2>
                    <div className="team-grid">
                        {Array.from({ length: 4 }, (_, index) => (
                            <div key={`teamA-${index}`} className="hero-slot">
                                {teamA[index] ? (
                                    <div className="selected-hero" style={{ borderColor: getTypeColor(teamA[index].type) }}>
                                        <button
                                            onClick={() => removeHeroFromTeam(teamA[index].id, 'A')}
                                            className="remove-hero-btn"
                                        >
                                            ❌
                                        </button>
                                        <div className="hero-name">{teamA[index].name}</div>
                                        <div className="hero-stars">{getStarDisplay(teamA[index].star)}</div>
                                        <div className="hero-stats">
                                            <span>❤️{teamA[index].hp}</span>
                                            <span>⚔️{teamA[index].attack}</span>
                                            <span>🛡️{teamA[index].defense}</span>
                                            <span>⚡{teamA[index].speed}</span>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="empty-slot">
                                        <span>Slot {index + 1}</span>
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                </div>

                {/* Équipe B */}
                <div className="team-section">
                    <h2 className="team-title team-b">🔴 Équipe B ({teamB.length}/4)</h2>
                    <div className="team-grid">
                        {Array.from({ length: 4 }, (_, index) => (
                            <div key={`teamB-${index}`} className="hero-slot">
                                {teamB[index] ? (
                                    <div className="selected-hero" style={{ borderColor: getTypeColor(teamB[index].type) }}>
                                        <button
                                            onClick={() => removeHeroFromTeam(teamB[index].id, 'B')}
                                            className="remove-hero-btn"
                                        >
                                            ❌
                                        </button>
                                        <div className="hero-name">{teamB[index].name}</div>
                                        <div className="hero-stars">{getStarDisplay(teamB[index].star)}</div>
                                        <div className="hero-stats">
                                            <span>❤️{teamB[index].hp}</span>
                                            <span>⚔️{teamB[index].attack}</span>
                                            <span>🛡️{teamB[index].defense}</span>
                                            <span>⚡{teamB[index].speed}</span>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="empty-slot">
                                        <span>Slot {index + 1}</span>
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            {/* Liste des héros disponibles */}
            <div className="available-heroes-section">
                <h3>📚 Héros Disponibles ({availableHeroes.length})</h3>
                <div className="heroes-grid">
                    {availableHeroes.map(hero => {
                        const heroSelection = isHeroSelected(hero.id);
                        return (
                            <div
                                key={hero.id}
                                className={`hero-card ${heroSelection ? 'selected' : ''} ${heroSelection === 'both' ? 'in-both-teams' : ''}`}
                                style={{ borderColor: getTypeColor(hero.type) }}
                            >
                                <div className="hero-name">{hero.name}</div>
                                <div className="hero-stars">{getStarDisplay(hero.star)}</div>
                                <div className="hero-stats">
                                    <span>❤️{hero.hp}</span>
                                    <span>⚔️{hero.attack}</span>
                                    <span>🛡️{hero.defense}</span>
                                    <span>⚡{hero.speed}</span>
                                    <span>🔰{hero.resistance}</span>
                                </div>
                                {heroSelection && (
                                    <div className="hero-selection-status">
                                        {heroSelection === 'both' && '🔵🔴 Dans les deux équipes'}
                                        {heroSelection === 'A' && '🔵 Dans Équipe A'}
                                        {heroSelection === 'B' && '🔴 Dans Équipe B'}
                                    </div>
                                )}
                                <div className="hero-actions">
                                    <button
                                        onClick={() => addHeroToTeam(hero, 'A')}
                                        disabled={teamA.length >= 4}
                                        className={`add-to-team-btn team-a-btn ${heroSelection === 'A' || heroSelection === 'both' ? 'hero-in-team' : ''} ${teamA.length >= 4 ? 'disabled' : ''}`}
                                    >
                                        {teamA.find(h => h.id === hero.id) ? '✓ Dans Équipe A' : '+ Équipe A'}
                                    </button>
                                    <button
                                        onClick={() => addHeroToTeam(hero, 'B')}
                                        disabled={teamB.length >= 4}
                                        className={`add-to-team-btn team-b-btn ${heroSelection === 'B' || heroSelection === 'both' ? 'hero-in-team' : ''} ${teamB.length >= 4 ? 'disabled' : ''}`}
                                    >
                                        {teamB.find(h => h.id === hero.id) ? '✓ Dans Équipe B' : '+ Équipe B'}
                                    </button>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>

            {/* Bouton de lancement du combat */}
            <div className="combat-section">
                <button
                    onClick={handleStartCombat}
                    disabled={!isTeamsReady}
                    className={`launch-combat-btn ${isTeamsReady ? 'ready' : 'not-ready'}`}
                >
                    {isTeamsReady ? '🚀 DÉMARRER LE COMBAT' : `⚠️ Sélectionnez 4 héros par équipe (${teamA.length + teamB.length}/8)`}
                </button>
            </div>
        </div>
    );
};

export default ChoixEquipe;