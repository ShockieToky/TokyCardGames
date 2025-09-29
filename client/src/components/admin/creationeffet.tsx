import React, { useState, useEffect } from 'react';
import '../../styles/admin.css';

const API_URL = 'http://localhost:8000';

interface SkillEffect {
    id: number;
    name: string;
    description: string;
    createdAt: string;
}

const CreationEffet: React.FC = () => {
    const [effects, setEffects] = useState<SkillEffect[]>([]);
    const [newEffect, setNewEffect] = useState<{ name: string; description: string }>({
        name: '',
        description: ''
    });
    const [editingEffect, setEditingEffect] = useState<SkillEffect | null>(null);
    const [message, setMessage] = useState<string>('');
    const [loading, setLoading] = useState<boolean>(false);
    const [error, setError] = useState<string | null>(null);

    // Récupérer tous les effets au chargement
    useEffect(() => {
        fetchEffects();
    }, []);

    const fetchEffects = () => {
        setLoading(true);
        fetch(`${API_URL}/skill/effects`, {
            credentials: 'include'
        })
            .then(res => {
                if (!res.ok) {
                    throw new Error(`Erreur HTTP: ${res.status}`);
                }
                return res.json();
            })
            .then(data => {
                setEffects(data);
                setLoading(false);
                setError(null);
            })
            .catch(err => {
                console.error('Erreur lors de la récupération des effets:', err);
                setError(`Erreur de chargement: ${err.message}`);
                setLoading(false);
            });
    };

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
        const { name, value } = e.target;
        if (editingEffect) {
            setEditingEffect({ ...editingEffect, [name]: value });
        } else {
            setNewEffect({ ...newEffect, [name]: value });
        }
    };

    const addEffect = (e: React.FormEvent) => {
        e.preventDefault();
        if (!newEffect.name.trim()) {
            setMessage('Le nom de l\'effet est requis');
            return;
        }

        setLoading(true);
        fetch(`${API_URL}/skill/effect/add`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify(newEffect)
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
                    setNewEffect({ name: '', description: '' });
                    fetchEffects();  // Actualiser la liste
                } else {
                    setMessage(`Erreur: ${data.error || 'Erreur inconnue'}`);
                }
                setLoading(false);
            })
            .catch(err => {
                console.error('Erreur lors de l\'ajout de l\'effet:', err);
                setMessage(`Erreur: ${err.message}`);
                setLoading(false);
            });
    };

    const startEditEffect = (effect: SkillEffect) => {
        setEditingEffect(effect);
        setMessage('');
    };

    const cancelEdit = () => {
        setEditingEffect(null);
        setMessage('');
    };

    const updateEffect = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingEffect) return;
        if (!editingEffect.name.trim()) {
            setMessage('Le nom de l\'effet est requis');
            return;
        }

        setLoading(true);
        fetch(`${API_URL}/skill/effect/${editingEffect.id}/edit`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
                name: editingEffect.name,
                description: editingEffect.description
            })
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
                    setMessage('Effet mis à jour avec succès!');
                    setEditingEffect(null);
                    fetchEffects();  // Actualiser la liste
                } else {
                    setMessage(`Erreur: ${data.error || 'Erreur inconnue'}`);
                }
                setLoading(false);
            })
            .catch(err => {
                console.error('Erreur lors de la mise à jour de l\'effet:', err);
                setMessage(`Erreur: ${err.message}`);
                setLoading(false);
            });
    };

    const deleteEffect = (id: number) => {
        if (!window.confirm('Êtes-vous sûr de vouloir supprimer cet effet?')) return;

        setLoading(true);
        fetch(`${API_URL}/skill/effect/${id}/delete`, {
            method: 'DELETE',
            credentials: 'include'
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
                    setMessage('Effet supprimé avec succès!');
                    fetchEffects();  // Actualiser la liste
                } else {
                    setMessage(`Erreur: ${data.error || 'Erreur inconnue'}`);
                }
                setLoading(false);
            })
            .catch(err => {
                console.error('Erreur lors de la suppression de l\'effet:', err);
                setMessage(`Erreur: ${err.message}`);
                setLoading(false);
            });
    };

    return (
        <div className="admin-container">
            <h2>Gestion des effets de compétence</h2>

            {message && <div className={message.includes('Erreur') ? 'error-message' : 'success-message'}>
                {message}
            </div>}

            {error && <div className="error-message">
                {error}
            </div>}

            {/* Formulaire pour ajouter/modifier un effet */}
            <div className="effect-form-container">
                <h3>{editingEffect ? 'Modifier un effet' : 'Ajouter un nouvel effet'}</h3>
                <form onSubmit={editingEffect ? updateEffect : addEffect} className="effect-form">
                    <div className="form-group">
                        <label htmlFor="name">Nom de l'effet:</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value={editingEffect ? editingEffect.name : newEffect.name}
                            onChange={handleInputChange}
                            placeholder="Ex: Augmentation d'attaque"
                            required
                        />
                    </div>
                    <div className="form-group">
                        <label htmlFor="description">Description:</label>
                        <textarea
                            id="description"
                            name="description"
                            value={editingEffect ? editingEffect.description : newEffect.description}
                            onChange={handleInputChange}
                            placeholder="Décrivez l'effet..."
                            rows={4}
                        />
                    </div>
                    <div className="form-actions">
                        <button type="submit" disabled={loading}>
                            {loading ? 'Traitement...' : (editingEffect ? 'Mettre à jour' : 'Ajouter')}
                        </button>
                        {editingEffect && (
                            <button type="button" onClick={cancelEdit} className="cancel-btn">
                                Annuler
                            </button>
                        )}
                    </div>
                </form>
            </div>

            {/* Liste des effets */}
            <div className="effects-list">
                <h3>Liste des effets existants</h3>
                {loading && !editingEffect && <p>Chargement...</p>}

                {effects.length === 0 && !loading ? (
                    <p className="no-data">Aucun effet n'a été créé.</p>
                ) : (
                    <table className="effects-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Description</th>
                                <th>Date de création</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {effects.map(effect => (
                                <tr key={effect.id}>
                                    <td>{effect.id}</td>
                                    <td>{effect.name}</td>
                                    <td className="description-cell">{effect.description}</td>
                                    <td>{new Date(effect.createdAt).toLocaleDateString()}</td>
                                    <td>
                                        <div className="action-buttons">
                                            <button onClick={() => startEditEffect(effect)} className="edit-btn">
                                                Modifier
                                            </button>
                                            <button onClick={() => deleteEffect(effect.id)} className="delete-btn">
                                                Supprimer
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        </div>
    );
};

export default CreationEffet;