import React, { useState, useEffect } from 'react';

const API_URL = 'http://localhost:8000';

interface SkillEffect {
    id: number;
    effect_type: string;
    value: number;
    chance: number;
    duration: number;
    scale_on: string;
    target_side: string;
    cumulative: boolean;
}

interface SkillEffectsProps {
    skillId: number;
    onEffectChange?: () => void;
}

// Constantes pour les valeurs et durées prédéfinies
const BUFF_VALUES = {
    // Effets positifs (buffs)
    buff_hp: 10, // 10% des PV max
    buff_defense: 10, // 10% de la DEF max
    buff_attack: 10, // 10% de l'ATK max
    buff_speed: 15, // 15% de la VIT max
    buff_resistance: 25, // +25 de résistance (valeur fixe)

    // Effets négatifs (débuffs)
    debuff_defense: 15, // -15% de défense
    debuff_speed: 15, // -15% de vitesse  
    debuff_attack: 20, // -20% d'attaque
    debuff_resistance: 20, // -20 de résistance

    // Effets spéciaux
    dot: 5, // 5% des PV max en dégâts continus
    shield: 20, // 20% des PV du lanceur
    lifesteal: 15, // 15% de vol de vie
    counter: 25, // 25% de chance de contre
    resurrection: 15, // 15% des PV pour résurrection
};

// Durées fixes pour certains effets
const FIXED_DURATIONS = {
    stun: 1, // Le stun dure toujours 1 tour
    freeze: 1, // Le gel dure toujours 1 tour
};

const SkillEffects: React.FC<SkillEffectsProps> = ({ skillId, onEffectChange }) => {
    const [effects, setEffects] = useState<SkillEffect[]>([]);
    const [loading, setLoading] = useState<boolean>(false);
    const [message, setMessage] = useState<string | null>(null);

    // Charger les effets au chargement du composant et quand skillId change
    useEffect(() => {
        if (!skillId) return;

        setLoading(true);
        fetch(`${API_URL}/skill/${skillId}/effects`, {
            credentials: 'include'
        })
            .then(res => res.json())
            .then(data => {
                setEffects(data);
                setLoading(false);
            })
            .catch(err => {
                console.error(`Erreur lors de la récupération des effets de la compétence ${skillId}:`, err);
                setLoading(false);
            });
    }, [skillId]);

    const updateEffectField = (effectId: number, field: keyof SkillEffect, value: any) => {
        setEffects(prevEffects =>
            prevEffects.map(effect => {
                if (effect.id === effectId) {
                    const newEffect = { ...effect, [field]: value };

                    // Si on change le type d'effet, mettre à jour la valeur et la durée si nécessaire
                    if (field === 'effect_type') {
                        // Mise à jour de la valeur si c'est un effet à valeur fixe
                        if (BUFF_VALUES[value as keyof typeof BUFF_VALUES]) {
                            newEffect.value = BUFF_VALUES[value as keyof typeof BUFF_VALUES];
                        }

                        // Mise à jour de la durée si c'est un effet à durée fixe
                        if (FIXED_DURATIONS[value as keyof typeof FIXED_DURATIONS]) {
                            newEffect.duration = FIXED_DURATIONS[value as keyof typeof FIXED_DURATIONS];
                        }

                        // Mise à jour de la cible selon le type d'effet
                        if (value.startsWith('buff_') || value === 'shield' || value === 'protection' ||
                            value === 'bloodthirst' || value === 'counter' || value === 'rescue') {
                            newEffect.target_side = 'ally'; // Les buffs vont généralement sur les alliés
                        } else {
                            newEffect.target_side = 'enemy'; // Les debuffs vont généralement sur les ennemis
                        }
                    }

                    return newEffect;
                }
                return effect;
            })
        );
    };

    const addNewEffect = () => {
        setLoading(true);
        const newEffect = {
            skillId: skillId,
            effect_type: "buff_attack",
            value: BUFF_VALUES.buff_attack,
            chance: 100,
            duration: 2,
            scale_on: "{}",
            target_side: "ally", // Par défaut sur un allié pour les buffs
            cumulative: false
        };

        fetch(`${API_URL}/skill/effect/add`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify(newEffect)
        })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.id) {
                    // Récupérer à nouveau les effets pour rafraîchir la liste
                    fetch(`${API_URL}/skill/${skillId}/effects`, {
                        credentials: 'include'
                    })
                        .then(res => res.json())
                        .then(updatedEffects => {
                            setEffects(updatedEffects);
                            setMessage('Nouvel effet ajouté avec succès');
                            if (onEffectChange) onEffectChange();
                        })
                        .catch(err => {
                            console.error('Erreur lors de la récupération des effets mis à jour:', err);
                        });
                } else {
                    setMessage(`Erreur: ${data.error || 'Erreur inconnue'}`);
                }
                setLoading(false);
            })
            .catch(err => {
                console.error('Erreur lors de l\'ajout de l\'effet:', err);
                setMessage('Erreur lors de l\'ajout de l\'effet');
                setLoading(false);
            });
    };

    const deleteEffect = (effectId: number) => {
        if (!window.confirm("Êtes-vous sûr de vouloir supprimer cet effet?")) return;

        setLoading(true);
        fetch(`${API_URL}/skill/effect/${effectId}/delete`, {
            method: 'DELETE',
            credentials: 'include'
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    setEffects(prev => prev.filter(effect => effect.id !== effectId));
                    setMessage('Effet supprimé avec succès');
                    if (onEffectChange) onEffectChange();
                } else {
                    setMessage(`Erreur: ${data.error || 'Erreur inconnue'}`);
                }
                setLoading(false);
            })
            .catch(err => {
                console.error('Erreur lors de la suppression de l\'effet:', err);
                setMessage('Erreur lors de la suppression de l\'effet');
                setLoading(false);
            });
    };

    const saveEffectChanges = (effect: SkillEffect) => {
        setLoading(true);
        fetch(`${API_URL}/skill/effect/${effect.id}/edit`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify(effect)
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    setMessage(`Effet mis à jour avec succès`);
                    if (onEffectChange) onEffectChange();
                } else {
                    setMessage(`Erreur: ${data.error || 'Erreur inconnue'}`);
                }
                setLoading(false);
            })
            .catch(err => {
                console.error('Erreur lors de la mise à jour de l\'effet:', err);
                setMessage('Erreur lors de la mise à jour de l\'effet');
                setLoading(false);
            });
    };

    // Fonction pour déterminer si le champ "valeur" doit être désactivé
    const isValueFieldDisabled = (effectType: string): boolean => {
        return !!BUFF_VALUES[effectType as keyof typeof BUFF_VALUES];
    };

    // Fonction pour déterminer si le champ "durée" doit être désactivé
    const isDurationFieldDisabled = (effectType: string): boolean => {
        return !!FIXED_DURATIONS[effectType as keyof typeof FIXED_DURATIONS];
    };

    return (
        <div className="skill-effects">
            <div className="effects-header">
                <h5>Effets</h5>
                <button
                    className="bouton-admin"
                    onClick={addNewEffect}
                    disabled={loading}
                >
                    Ajouter un effet
                </button>
            </div>

            {message && <div className="message">{message}</div>}
            {loading && <div className="loading">Chargement...</div>}

            {effects.length > 0 ? (
                effects.map(effect => (
                    <div key={effect.id} className="effect-card">
                        <div className="effect-header">
                            <h6>Effet</h6>
                            <button
                                className="delete-btn small"
                                onClick={() => deleteEffect(effect.id)}
                                disabled={loading}
                            >
                                Supprimer
                            </button>
                        </div>

                        <div className="form-row">
                            <div className="form-group">
                                <label>Type d'effet:</label>
                                <select
                                    value={effect.effect_type}
                                    onChange={e => updateEffectField(effect.id, 'effect_type', e.target.value)}
                                >
                                    {/* Effets positifs */}
                                    <optgroup label="Effets positifs">
                                        <option value="buff_hp">Buff PV (+10%)</option>
                                        <option value="buff_defense">Buff Défense (+10%)</option>
                                        <option value="buff_attack">Buff Attaque (+10%)</option>
                                        <option value="buff_speed">Buff Vitesse (+15%)</option>
                                        <option value="buff_resistance">Buff Résistance (+25)</option>
                                        <option value="shield">Bouclier (20% PV lanceur)</option>
                                        <option value="protection">Protection</option>
                                        <option value="lifesteal">Soif de sang (15%)</option>
                                        <option value="counter">Contre (25%)</option>
                                        <option value="resurrection">Sauvetage (15%)</option>
                                    </optgroup>

                                    {/* Effets négatifs */}
                                    <optgroup label="Effets négatifs">
                                        <option value="debuff_attack">Débuff Attaque (-20%)</option>
                                        <option value="debuff_defense">Débuff Défense (-15%)</option>
                                        <option value="debuff_speed">Débuff Vitesse (-15%)</option>
                                        <option value="debuff_resistance">Débuff Résistance (-20)</option>
                                        <option value="stun">Étourdissement (1 tour)</option>
                                        <option value="silence">Silence</option>
                                        <option value="nullify">Annulation</option>
                                        <option value="blocker">Bloqueur</option>
                                        <option value="taunt">Provocation</option>
                                        <option value="heal_reverse">Soins Mortels</option>
                                        <option value="damage_over_time">Dégâts continus (5% PV)</option>
                                        <option value="freeze">Gel (1 tour)</option>
                                    </optgroup>
                                </select>
                            </div>
                            <div className="form-group">
                                <label>Valeur:</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    value={effect.value}
                                    onChange={e => updateEffectField(effect.id, 'value', parseFloat(e.target.value))}
                                    disabled={isValueFieldDisabled(effect.effect_type)}
                                    title={isValueFieldDisabled(effect.effect_type) ? "Valeur automatique pour ce type d'effet" : ""}
                                />
                                {isValueFieldDisabled(effect.effect_type) &&
                                    <small className="form-text text-info">
                                        {effect.effect_type === 'dot'
                                            ? "Fixé à 5% des PV max"
                                            : "Valeur fixée automatiquement"}
                                    </small>
                                }
                            </div>
                        </div>
                        <div className="form-row">
                            <div className="form-group">
                                <label>Chance (%):</label>
                                <input
                                    type="number"
                                    value={effect.chance}
                                    onChange={e => updateEffectField(effect.id, 'chance', parseInt(e.target.value))}
                                />
                            </div>
                            <div className="form-group">
                                <label>Durée (tours):</label>
                                <input
                                    type="number"
                                    value={effect.duration}
                                    onChange={e => updateEffectField(effect.id, 'duration', parseInt(e.target.value))}
                                    disabled={isDurationFieldDisabled(effect.effect_type)}
                                    title={isDurationFieldDisabled(effect.effect_type) ? "Durée fixe pour ce type d'effet" : ""}
                                />
                                {isDurationFieldDisabled(effect.effect_type) &&
                                    <small className="form-text text-info">
                                        {effect.effect_type === 'stun' && "Fixé à 1 tour"}
                                    </small>
                                }
                            </div>
                        </div>
                        <div className="form-row">
                            <div className="form-group">
                                <label>Côté cible:</label>
                                <select
                                    value={effect.target_side}
                                    onChange={e => updateEffectField(effect.id, 'target_side', e.target.value)}
                                >
                                    <option value="enemy">Ennemi</option>
                                    <option value="ally">Allié</option>
                                    <option value="self">Soi-même</option>
                                </select>
                            </div>
                            <div className="form-group">
                                <label>Cumulable:</label>
                                <select
                                    value={effect.cumulative ? "true" : "false"}
                                    onChange={e => updateEffectField(effect.id, 'cumulative', e.target.value === "true")}
                                >
                                    <option value="false">Non</option>
                                    <option value="true">Oui</option>
                                </select>
                            </div>
                        </div>
                        <button
                            onClick={() => saveEffectChanges(effect)}
                            disabled={loading}
                        >
                            Enregistrer l'effet
                        </button>
                    </div>
                ))
            ) : (
                <div className="no-effects">
                    <p>Aucun effet pour cette compétence</p>
                </div>
            )}
        </div>
    );
};

export default SkillEffects;