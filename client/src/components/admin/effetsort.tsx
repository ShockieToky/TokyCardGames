import React, { useState, useEffect } from 'react';
import '../../styles/admin.css';

const API_URL = 'http://localhost:8000';

interface SkillEffect {
    id: number;
    name: string;
    description: string;
}

interface LinkedEffect {
    id: number;
    effect: {
        id: number;
        name: string;
        description: string;
    };
    duration: number;
    accuracy: number;
    value: number;
    fullDescription: string;
}

interface SkillEffectsModalProps {
    skillId: number;
    skillName: string;
    onClose: () => void;
    onEffectChange?: () => void;
}

const SkillEffectsModal: React.FC<SkillEffectsModalProps> = ({
    skillId,
    skillName,
    onClose,
    onEffectChange
}) => {
    const [availableEffects, setAvailableEffects] = useState<SkillEffect[]>([]);
    const [linkedEffects, setLinkedEffects] = useState<LinkedEffect[]>([]);
    const [isLoading, setIsLoading] = useState<boolean>(false);
    const [message, setMessage] = useState<string>('');
    const [isAddingEffect, setIsAddingEffect] = useState<boolean>(false);

    // Nouvel effet à ajouter
    const [newEffectData, setNewEffectData] = useState({
        effectId: 0,
        duration: 1,
        accuracy: 100,
        value: 0
    });

    // Récupérer les données au chargement
    useEffect(() => {
        setIsLoading(true);

        // Récupérer les effets liés à cette compétence
        fetch(`${API_URL}/link/skill/effect/skill/${skillId}`, {
            credentials: 'include'
        })
            .then(res => {
                if (!res.ok) throw new Error(`Erreur HTTP ${res.status}`);
                return res.json();
            })
            .then(data => {
                setLinkedEffects(data);
                setIsLoading(false);
            })
            .catch(err => {
                console.error('Erreur lors de la récupération des effets liés:', err);
                setMessage(`Erreur: ${err.message}`);
                setIsLoading(false);
            });

        // Récupérer tous les effets disponibles
        fetch(`${API_URL}/link/skill/effect/available/effects`, {
            credentials: 'include'
        })
            .then(res => {
                if (!res.ok) throw new Error(`Erreur HTTP ${res.status}`);
                return res.json();
            })
            .then(data => {
                setAvailableEffects(data);
                if (data.length > 0) {
                    setNewEffectData(prev => ({
                        ...prev,
                        effectId: data[0].id
                    }));
                }
            })
            .catch(err => {
                console.error('Erreur lors de la récupération des effets disponibles:', err);
                setMessage(`Erreur: ${err.message}`);
            });
    }, [skillId]);

    // Gestion des changements de formulaire
    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setNewEffectData(prev => ({
            ...prev,
            [name]: name === 'effectId' ? parseInt(value) :
                name === 'duration' ? parseInt(value) :
                    name === 'accuracy' ? parseInt(value) :
                        parseFloat(value)
        }));
    };

    // Ajouter un effet
    const addEffectToSkill = (e: React.FormEvent) => {
        e.preventDefault();
        setIsLoading(true);

        const linkData = {
            skillId: skillId,
            effectId: newEffectData.effectId,
            duration: newEffectData.duration,
            accuracy: newEffectData.accuracy,
            value: newEffectData.value
        };

        fetch(`${API_URL}/link/skill/effect/add`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify(linkData)
        })
            .then(res => {
                if (!res.ok) {
                    return res.text().then(text => {
                        throw new Error(`Erreur HTTP ${res.status}: ${text.substring(0, 100)}...`);
                    });
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    setMessage('Effet ajouté avec succès!');
                    setIsAddingEffect(false);

                    // Actualiser la liste des effets
                    fetch(`${API_URL}/link/skill/effect/skill/${skillId}`, {
                        credentials: 'include'
                    })
                        .then(res => res.json())
                        .then(updatedEffects => {
                            setLinkedEffects(updatedEffects);
                            if (onEffectChange) onEffectChange();
                        })
                        .catch(err => {
                            console.error('Erreur lors de la récupération des effets mis à jour:', err);
                        });
                } else {
                    setMessage(`Erreur: ${data.error || 'Erreur inconnue'}`);
                }
                setIsLoading(false);
            })
            .catch(err => {
                console.error('Erreur lors de l\'ajout de l\'effet:', err);
                setMessage(`Erreur: ${err.message}`);
                setIsLoading(false);
            });
    };

    // Supprimer un effet
    const removeEffect = (linkId: number) => {
        if (!window.confirm('Êtes-vous sûr de vouloir supprimer cet effet?')) return;

        setIsLoading(true);
        fetch(`${API_URL}/link/skill/effect/${linkId}/delete`, {
            method: 'DELETE',
            credentials: 'include'
        })
            .then(res => {
                if (!res.ok) throw new Error(`Erreur HTTP ${res.status}`);
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    setLinkedEffects(prev => prev.filter(link => link.id !== linkId));
                    setMessage('Effet supprimé avec succès!');
                    if (onEffectChange) onEffectChange();
                } else {
                    setMessage(`Erreur: ${data.error || 'Erreur inconnue'}`);
                }
                setIsLoading(false);
            })
            .catch(err => {
                console.error('Erreur lors de la suppression de l\'effet:', err);
                setMessage(`Erreur: ${err.message}`);
                setIsLoading(false);
            });
    };

    return (
        <div className="skill-effects-modal-overlay" onClick={onClose}>
            <div className="skill-effects-modal-content" onClick={e => e.stopPropagation()}>
                <div className="modal-header">
                    <h3>Effets de la compétence : {skillName}</h3>
                    <button className="close-button" onClick={onClose}>×</button>
                </div>

                {message && (
                    <div className={message.includes('Erreur') ? 'error-message' : 'success-message'}>
                        {message}
                    </div>
                )}

                <div className="modal-body">
                    <div className="linked-effects-section">
                        <h4>Effets actuels</h4>
                        {isLoading && <p>Chargement...</p>}

                        {!isLoading && linkedEffects.length === 0 ? (
                            <p className="no-effects">Aucun effet associé à cette compétence</p>
                        ) : (
                            <ul className="linked-effects-list">
                                {linkedEffects.map(link => (
                                    <li key={link.id} className="linked-effect-item">
                                        <div className="effect-details">
                                            <strong>{link.effect.name}</strong>
                                            <span className="effect-params">
                                                {link.value !== 0 && <span>Valeur: {link.value}, </span>}
                                                Durée: {link.duration} tour(s),
                                                Précision: {link.accuracy}%
                                            </span>
                                            <p className="effect-description">{link.effect.description}</p>
                                        </div>
                                        <button
                                            className="delete-effect-btn"
                                            onClick={() => removeEffect(link.id)}
                                            disabled={isLoading}
                                        >
                                            Supprimer
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>

                    {isAddingEffect ? (
                        <div className="add-effect-form-container">
                            <h4>Ajouter un effet</h4>
                            <form onSubmit={addEffectToSkill} className="add-effect-form">
                                <div className="form-group">
                                    <label htmlFor="effectId">Effet:</label>
                                    <select
                                        id="effectId"
                                        name="effectId"
                                        value={newEffectData.effectId}
                                        onChange={handleInputChange}
                                        disabled={isLoading || availableEffects.length === 0}
                                        required
                                    >
                                        {availableEffects.map(effect => (
                                            <option key={effect.id} value={effect.id}>
                                                {effect.name} - {effect.description.substring(0, 50)}
                                                {effect.description.length > 50 ? '...' : ''}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div className="form-row">
                                    <div className="form-group">
                                        <label htmlFor="value">Valeur:</label>
                                        <input
                                            type="number"
                                            id="value"
                                            name="value"
                                            value={newEffectData.value}
                                            onChange={handleInputChange}
                                            step="0.1"
                                        />
                                        <small>% ou valeur absolue</small>
                                    </div>

                                    <div className="form-group">
                                        <label htmlFor="duration">Durée (tours):</label>
                                        <input
                                            type="number"
                                            id="duration"
                                            name="duration"
                                            value={newEffectData.duration}
                                            onChange={handleInputChange}
                                            min="1"
                                            required
                                        />
                                    </div>

                                    <div className="form-group">
                                        <label htmlFor="accuracy">Précision (%):</label>
                                        <input
                                            type="number"
                                            id="accuracy"
                                            name="accuracy"
                                            value={newEffectData.accuracy}
                                            onChange={handleInputChange}
                                            min="1"
                                            max="100"
                                            required
                                        />
                                    </div>
                                </div>

                                <div className="form-buttons">
                                    <button
                                        type="submit"
                                        className="bouton-admin"
                                        disabled={isLoading}
                                    >
                                        {isLoading ? "En cours..." : "Ajouter l'effet"}
                                    </button>
                                    <button
                                        type="button"
                                        className="cancel-btn"
                                        onClick={() => setIsAddingEffect(false)}
                                        disabled={isLoading}
                                    >
                                        Annuler
                                    </button>
                                </div>
                            </form>
                        </div>
                    ) : (
                        <button
                            className="bouton-admin add-effect-btn"
                            onClick={() => setIsAddingEffect(true)}
                            disabled={isLoading}
                        >
                            Ajouter un nouvel effet
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
};

export default SkillEffectsModal;