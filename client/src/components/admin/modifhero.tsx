import React, { useState, useEffect } from 'react';
import SkillEffectsModal from './effetsort';

const API_URL = 'http://localhost:8000';

interface Hero {
    id: number;
    name: string;
    HP: number;
    DEF: number;
    ATK: number;
    VIT: number;
    RES: number;
    star: number;
    type: number; // PV = 0; Défense = 1; Attaque = 2
}

interface HeroSkill {
    id: number;
    name: string;
    description: string;
    multiplicator: number;
    scaling: string;
    hits_number: number;
    cooldown: number;
    initial_cooldown: number;
    is_passive: boolean;
    targeting: string; // Format JSON pour le type de ciblage
    targeting_team: string;
    does_damage: boolean;
}

const ModifHero: React.FC = () => {
    const [heroes, setHeroes] = useState<Hero[]>([]);
    const [selectedHeroId, setSelectedHeroId] = useState<number | null>(null);
    const [heroDetails, setHeroDetails] = useState<Hero | null>(null);
    const [heroSkills, setHeroSkills] = useState<HeroSkill[]>([]);
    const [message, setMessage] = useState<string | null>(null);
    const [loading, setLoading] = useState<boolean>(false);
    const [activeEffectSkill, setActiveEffectSkill] = useState<{ id: number, name: string } | null>(null);

    // Fonction pour parser et obtenir la stat de scaling actuelle
    const getScalingStat = (scalingJson: string): string => {
        try {
            const scaling = JSON.parse(scalingJson);
            return scaling.stat || 'ATK';
        } catch (e) {
            return 'ATK'; // Par défaut ATK si le JSON est invalide
        }
    };

    // Fonction pour mettre à jour le scaling d'une compétence
    const updateScaling = (skillId: number, stat: string) => {
        const scalingJson = JSON.stringify({ stat });
        updateSkillField(skillId, 'scaling', scalingJson);
    };

    // Fonction pour récupérer le type de ciblage actuel
    const getTargetingType = (targetingJson: string): string => {
        try {
            const targeting = JSON.parse(targetingJson);
            return targeting.type || 'mono';
        } catch (e) {
            return 'mono'; // Par défaut mono si le JSON est invalide
        }
    };

    // Fonction pour mettre à jour le type de ciblage
    const updateTargetingType = (skillId: number, type: string) => {
        const targetingJson = JSON.stringify({ type });
        updateSkillField(skillId, 'targeting', targetingJson);
    };

    // Récupère la liste des héros
    useEffect(() => {
        fetch(`${API_URL}/heroes`, {
            credentials: 'include'
        })
            .then(res => res.json())
            .then(data => {
                setHeroes(data);
            })
            .catch(err => {
                console.error('Erreur lors de la récupération des héros:', err);
                setMessage('Erreur lors de la récupération des héros');
            });
    }, []);

    // Récupère les détails du héros sélectionné et ses compétences
    useEffect(() => {
        if (!selectedHeroId) return;

        setLoading(true);
        setHeroSkills([]);

        fetch(`${API_URL}/heroes/${selectedHeroId}`, {
            credentials: 'include'
        })
            .then(res => res.json())
            .then(data => {
                setHeroDetails(data);
                setLoading(false);
            })
            .catch(err => {
                console.error('Erreur lors de la récupération des détails du héros:', err);
                setMessage('Erreur lors de la récupération des détails du héros');
                setLoading(false);
            });

        // Récupère les compétences du héros
        fetch(`${API_URL}/hero/${selectedHeroId}/skills`, {
            credentials: 'include'
        })
            .then(res => {
                if (!res.ok) {
                    return res.text().then(text => {
                        console.error('Réponse d\'erreur du serveur:', text.substring(0, 200));
                        throw new Error(`Erreur HTTP ${res.status}`);
                    });
                }
                return res.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Réponse non-JSON:', text.substring(0, 200));
                        throw new Error('Format de réponse invalide');
                    }
                });
            })
            .then(data => {
                setHeroSkills(data);
            })
            .catch(err => {
                console.error('Erreur lors de la récupération des compétences:', err);
                setMessage(`Erreur: ${err.message}`);
            });
    }, [selectedHeroId]);

    const handleHeroChange = (event: React.ChangeEvent<HTMLSelectElement>) => {
        const heroId = parseInt(event.target.value);
        setSelectedHeroId(heroId);
        setMessage(null);
    };

    const updateHeroField = (field: keyof Hero, value: any) => {
        if (!heroDetails) return;

        setHeroDetails({
            ...heroDetails,
            [field]: value
        });
    };

    const updateSkillField = (skillId: number, field: keyof HeroSkill, value: any) => {
        setHeroSkills(prevSkills =>
            prevSkills.map(skill =>
                skill.id === skillId ? { ...skill, [field]: value } : skill
            )
        );
    };

    // Ajoute une nouvelle compétence au héros
    const addNewSkill = () => {
        if (!heroDetails || heroSkills.length >= 3) return;

        setLoading(true);
        const newSkill = {
            heroId: selectedHeroId,
            name: "Nouveau sort",
            description: "Description du sort",
            multiplicator: 1.0,
            scaling: JSON.stringify({ stat: 'ATK' }), // Scaling par défaut sur ATK
            hits_number: 1,
            cooldown: 0,
            initial_cooldown: 0,
            is_passive: false,
            targeting: JSON.stringify({ type: 'mono' }), // Ciblage par défaut sur mono
            targeting_team: "enemy",
            does_damage: true
        };

        fetch(`${API_URL}/hero/skill/add`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify(newSkill)
        })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.id) {
                    // Récupérer à nouveau les compétences pour rafraîchir la liste
                    fetch(`${API_URL}/hero/${selectedHeroId}/skills`, {
                        credentials: 'include'
                    })
                        .then(res => res.json())
                        .then(updatedSkills => {
                            setHeroSkills(updatedSkills);
                            setMessage('Nouvelle compétence ajoutée avec succès');
                        })
                        .catch(err => {
                            console.error('Erreur lors de la récupération des compétences mises à jour:', err);
                        });
                } else {
                    setMessage(`Erreur: ${data.error || 'Erreur inconnue'}`);
                }
                setLoading(false);
            })
            .catch(err => {
                console.error('Erreur lors de l\'ajout de la compétence:', err);
                setMessage('Erreur lors de l\'ajout de la compétence');
                setLoading(false);
            });
    };

    // Supprime une compétence
    const deleteSkill = (skillId: number) => {
        if (!window.confirm("Êtes-vous sûr de vouloir supprimer cette compétence?")) return;

        setLoading(true);
        fetch(`${API_URL}/hero/skill/${skillId}/delete`, {
            method: 'DELETE',
            credentials: 'include'
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    setHeroSkills(prev => prev.filter(skill => skill.id !== skillId));
                    setMessage('Compétence supprimée avec succès');
                } else {
                    setMessage(`Erreur: ${data.error || 'Erreur inconnue'}`);
                }
                setLoading(false);
            })
            .catch(err => {
                console.error('Erreur lors de la suppression de la compétence:', err);
                setMessage('Erreur lors de la suppression de la compétence');
                setLoading(false);
            });
    };

    const saveHeroChanges = () => {
        if (!heroDetails || !selectedHeroId) return;

        setLoading(true);
        fetch(`${API_URL}/heroes/${selectedHeroId}/edit`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify(heroDetails)
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    setMessage('Héros mis à jour avec succès');
                } else {
                    setMessage(`Erreur: ${data.error || 'Erreur inconnue'}`);
                }
                setLoading(false);
            })
            .catch(err => {
                console.error('Erreur lors de la mise à jour du héros:', err);
                setMessage('Erreur lors de la mise à jour du héros');
                setLoading(false);
            });
    };

    const saveSkillChanges = (skill: HeroSkill) => {
        setLoading(true);
        fetch(`${API_URL}/hero/skill/${skill.id}/edit`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify(skill)
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    setMessage(`Compétence ${skill.name} mise à jour avec succès`);
                } else {
                    setMessage(`Erreur: ${data.error || 'Erreur inconnue'}`);
                }
                setLoading(false);
            })
            .catch(err => {
                console.error('Erreur lors de la mise à jour de la compétence:', err);
                setMessage('Erreur lors de la mise à jour de la compétence');
                setLoading(false);
            });
    };

    // Conversion du type numérique en texte
    const getHeroTypeName = (type: number): string => {
        switch (type) {
            case 0: return "PV";
            case 1: return "Défense";
            case 2: return "Attaque";
            default: return "Inconnu";
        }
    };

    return (
        <div className="modif-hero-container">
            <h2>Modification d'un héros</h2>

            <div className="hero-selector">
                <label>
                    Sélectionner un héros:
                    <select onChange={handleHeroChange} value={selectedHeroId || ''}>
                        <option value="">-- Choisir un héros --</option>
                        {heroes.map(hero => (
                            <option key={hero.id} value={hero.id}>
                                {hero.name} ({hero.star}★) - {getHeroTypeName(hero.type)}
                            </option>
                        ))}
                    </select>
                </label>
            </div>

            {loading && <div className="loading">Chargement...</div>}
            {message && <div className="message">{message}</div>}

            {heroDetails && (
                <div className="hero-details">
                    <h3>Détails du héros</h3>
                    <div className="form-group">
                        <label>Nom:</label>
                        <input
                            type="text"
                            value={heroDetails.name}
                            onChange={e => updateHeroField('name', e.target.value)}
                        />
                    </div>
                    <div className="form-row">
                        <div className="form-group">
                            <label>HP:</label>
                            <input
                                type="number"
                                value={heroDetails.HP}
                                onChange={e => updateHeroField('HP', parseInt(e.target.value))}
                            />
                        </div>
                        <div className="form-group">
                            <label>DEF:</label>
                            <input
                                type="number"
                                value={heroDetails.DEF}
                                onChange={e => updateHeroField('DEF', parseInt(e.target.value))}
                            />
                        </div>
                        <div className="form-group">
                            <label>ATK:</label>
                            <input
                                type="number"
                                value={heroDetails.ATK}
                                onChange={e => updateHeroField('ATK', parseInt(e.target.value))}
                            />
                        </div>
                        <div className="form-group">
                            <label>VIT:</label>
                            <input
                                type="number"
                                value={heroDetails.VIT}
                                onChange={e => updateHeroField('VIT', parseInt(e.target.value))}
                            />
                        </div>
                        <div className="form-group">
                            <label>RES:</label>
                            <input
                                type="number"
                                value={heroDetails.RES}
                                onChange={e => updateHeroField('RES', parseInt(e.target.value))}
                            />
                        </div>
                    </div>
                    <div className="form-row">
                        <div className="form-group">
                            <label>Étoiles:</label>
                            <select
                                value={heroDetails.star}
                                onChange={e => updateHeroField('star', parseInt(e.target.value))}
                            >
                                {[1, 2, 3, 4, 5, 6].map(star => (
                                    <option key={star} value={star}>{star}★</option>
                                ))}
                            </select>
                        </div>
                        <div className="form-group">
                            <label>Type:</label>
                            <select
                                value={heroDetails.type}
                                onChange={e => updateHeroField('type', parseInt(e.target.value))}
                            >
                                <option value={0}>PV</option>
                                <option value={1}>Défense</option>
                                <option value={2}>Attaque</option>
                            </select>
                        </div>
                    </div>
                    <button className="bouton-admin" onClick={saveHeroChanges} disabled={loading}>
                        Enregistrer les modifications du héros
                    </button>
                </div>
            )}

            {heroDetails && (
                <div className="hero-skills">
                    <h3>Compétences (3 maximum)</h3>

                    {/* Afficher les 3 emplacements de sorts */}
                    {[0, 1, 2].map(index => {
                        const skill = heroSkills[index];

                        // Si le sort existe, afficher le formulaire d'édition
                        if (skill) {
                            return (
                                <div key={skill.id} className="skill-card">
                                    <div className="skill-header">
                                        <h4>Compétence {index + 1}</h4>
                                        <button
                                            className="delete-btn"
                                            onClick={() => deleteSkill(skill.id)}
                                            disabled={loading}
                                        >
                                            Supprimer
                                        </button>
                                    </div>

                                    <div className="form-group">
                                        <label>Nom:</label>
                                        <input
                                            type="text"
                                            value={skill.name}
                                            onChange={e => updateSkillField(skill.id, 'name', e.target.value)}
                                        />
                                    </div>
                                    <div className="form-group">
                                        <label>Description:</label>
                                        <textarea
                                            value={skill.description}
                                            onChange={e => updateSkillField(skill.id, 'description', e.target.value)}
                                        />
                                    </div>
                                    <div className="form-row">
                                        <div className="form-group">
                                            <label>Multiplicateur:</label>
                                            <div className="scaling-row">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    value={skill.multiplicator}
                                                    onChange={e => updateSkillField(skill.id, 'multiplicator', parseFloat(e.target.value))}
                                                    className="multiplicator-input"
                                                />
                                                <span className="scaling-multiply">×</span>
                                                <select
                                                    value={getScalingStat(skill.scaling)}
                                                    onChange={e => updateScaling(skill.id, e.target.value)}
                                                    className="scaling-select"
                                                >
                                                    <option value="ATK">ATK</option>
                                                    <option value="DEF">DEF</option>
                                                    <option value="HP">HP</option>
                                                    <option value="VIT">VIT</option>
                                                    <option value="RES">RES</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div className="form-group">
                                            <label>Nombre de coups:</label>
                                            <input
                                                type="number"
                                                value={skill.hits_number}
                                                onChange={e => updateSkillField(skill.id, 'hits_number', parseInt(e.target.value))}
                                            />
                                        </div>
                                    </div>
                                    <div className="form-row">
                                        <div className="form-group">
                                            <label>Cooldown:</label>
                                            <input
                                                type="number"
                                                value={skill.cooldown}
                                                onChange={e => updateSkillField(skill.id, 'cooldown', parseInt(e.target.value))}
                                            />
                                        </div>
                                        <div className="form-group">
                                            <label>Cooldown initial:</label>
                                            <input
                                                type="number"
                                                value={skill.initial_cooldown}
                                                onChange={e => updateSkillField(skill.id, 'initial_cooldown', parseInt(e.target.value))}
                                            />
                                        </div>
                                    </div>
                                    <div className="form-row">
                                        <div className="form-group">
                                            <label>Passif:</label>
                                            <select
                                                value={skill.is_passive ? "true" : "false"}
                                                onChange={e => updateSkillField(skill.id, 'is_passive', e.target.value === "true")}
                                            >
                                                <option value="false">Non</option>
                                                <option value="true">Oui</option>
                                            </select>
                                        </div>
                                        <div className="form-group">
                                            <label>Type de ciblage:</label>
                                            <select
                                                value={getTargetingType(skill.targeting)}
                                                onChange={e => updateTargetingType(skill.id, e.target.value)}
                                            >
                                                <option value="mono">Mono</option>
                                                <option value="multi">Multi</option>
                                                <option value="random">Aléatoire</option>
                                            </select>
                                        </div>
                                        <div className="form-group">
                                            <label>Ciblage d'équipe:</label>
                                            <select
                                                value={skill.targeting_team}
                                                onChange={e => updateSkillField(skill.id, 'targeting_team', e.target.value)}
                                            >
                                                <option value="enemy">Ennemi</option>
                                                <option value="ally">Allié</option>
                                                <option value="self">Soi-même</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div className="form-row">
                                        <div className="form-group">
                                            <label>Inflige des dégâts:</label>
                                            <select
                                                value={skill.does_damage ? "true" : "false"}
                                                onChange={e => updateSkillField(skill.id, 'does_damage', e.target.value === "true")}
                                            >
                                                <option value="false">Non</option>
                                                <option value="true">Oui</option>
                                            </select>
                                        </div>
                                    </div>
                                    <button
                                        className="bouton-admin effect-btn"
                                        onClick={() => setActiveEffectSkill({ id: skill.id, name: skill.name })}
                                        disabled={loading}
                                    >
                                        Gérer les effets
                                    </button>
                                    <button
                                        className="bouton-admin"
                                        onClick={() => saveSkillChanges(skill)}
                                        disabled={loading}
                                    >
                                        Enregistrer la compétence
                                    </button>
                                </div>
                            );
                        }
                        // Sinon, afficher un emplacement vide avec bouton d'ajout
                        else {
                            return (
                                <div key={`empty-${index}`} className="skill-card empty-skill">
                                    <h4>Compétence {index + 1}</h4>
                                    <p>Aucune compétence définie pour cet emplacement</p>
                                    <button
                                        className="bouton-admin"
                                        onClick={addNewSkill}
                                        disabled={loading || heroSkills.length >= 3}
                                    >
                                        Ajouter une compétence
                                    </button>
                                </div>
                            );
                        }
                    })}
                    {activeEffectSkill && (
                        <SkillEffectsModal
                            skillId={activeEffectSkill.id}
                            skillName={activeEffectSkill.name}
                            onClose={() => setActiveEffectSkill(null)}
                            onEffectChange={() => setMessage('Effets mis à jour avec succès')}
                        />
                    )}
                </div>
            )}
        </div>
    );
};

export default ModifHero;