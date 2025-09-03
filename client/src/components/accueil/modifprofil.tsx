import React, { useState, useEffect } from 'react';
import '../../styles/modifprofil.css';

const API_URL = 'http://localhost:8000';

const ModifProfil: React.FC = () => {
    const [isOpen, setIsOpen] = useState(false);
    const [pseudo, setPseudo] = useState('');
    const [oldPassword, setOldPassword] = useState('');
    const [newPassword, setNewPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState<{ text: string, type: 'success' | 'error' | '' }>({ text: '', type: '' });

    // Charger les infos de l'utilisateur quand la pop-up s'ouvre
    useEffect(() => {
        if (isOpen) {
            fetchUserInfo();
        }
    }, [isOpen]);

    const fetchUserInfo = async () => {
        try {
            const response = await fetch(`${API_URL}/user/me`, {
                method: 'GET',
                credentials: 'include',
            });
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    setPseudo(data.pseudo);
                }
            }
        } catch (error) {
            console.error('Erreur lors de la récupération des informations utilisateur:', error);
        }
    };

    const openModal = () => setIsOpen(true);
    const closeModal = () => {
        setIsOpen(false);
        resetForm();
    };

    const resetForm = () => {
        setOldPassword('');
        setNewPassword('');
        setConfirmPassword('');
        setMessage({ text: '', type: '' });
    };

    const validateForm = () => {
        // Vérifier si les champs sont remplis
        if (!oldPassword || !newPassword || !confirmPassword) {
            setMessage({ text: 'Tous les champs sont requis', type: 'error' });
            return false;
        }

        // Vérifier que le nouveau mot de passe et la confirmation correspondent
        if (newPassword !== confirmPassword) {
            setMessage({ text: 'Le nouveau mot de passe et sa confirmation ne correspondent pas', type: 'error' });
            return false;
        }

        // Vérifier que le nouveau mot de passe est suffisamment fort
        if (newPassword.length < 8) {
            setMessage({ text: 'Le nouveau mot de passe doit contenir au moins 8 caractères', type: 'error' });
            return false;
        }

        return true;
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!validateForm()) {
            return;
        }

        setLoading(true);

        try {
            const response = await fetch(`${API_URL}/user/password/update`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({
                    oldPassword,
                    newPassword,
                }),
            });

            const data = await response.json();

            if (data.success) {
                setMessage({ text: 'Mot de passe mis à jour avec succès!', type: 'success' });
                // Fermer la modal après 2 secondes en cas de succès
                setTimeout(() => {
                    closeModal();
                }, 2000);
            } else {
                setMessage({ text: data.error || 'Une erreur est survenue', type: 'error' });
            }
        } catch (error) {
            console.error('Erreur lors de la mise à jour du mot de passe:', error);
            setMessage({ text: 'Erreur de connexion au serveur', type: 'error' });
        } finally {
            setLoading(false);
        }
    };

    // Fermeture de la modal en cliquant à l'extérieur
    const handleOutsideClick = (e: React.MouseEvent<HTMLDivElement>) => {
        if (e.target === e.currentTarget) {
            closeModal();
        }
    };

    return (
        <>
            <button className="modify-profile-btn" onClick={openModal}>
                Modifier le profil
            </button>

            {isOpen && (
                <div className="modal-overlay" onClick={handleOutsideClick}>
                    <div className="modal-content">
                        <div className="modal-header">
                            <h3>Modification du profil</h3>
                            <button className="modal-close" onClick={closeModal}>&times;</button>
                        </div>

                        <form onSubmit={handleSubmit}>
                            <div className="form-group">
                                <label>Pseudo:</label>
                                <input
                                    type="text"
                                    value={pseudo}
                                    disabled
                                    className="disabled-input"
                                />
                                <small>Vous ne pouvez pas modifier votre pseudo</small>
                            </div>

                            <div className="form-group">
                                <label>Ancien mot de passe:</label>
                                <input
                                    type="password"
                                    value={oldPassword}
                                    onChange={(e) => setOldPassword(e.target.value)}
                                    required
                                />
                            </div>

                            <div className="form-group">
                                <label>Nouveau mot de passe:</label>
                                <input
                                    type="password"
                                    value={newPassword}
                                    onChange={(e) => setNewPassword(e.target.value)}
                                    required
                                />
                                <small>Le mot de passe doit contenir au moins 8 caractères</small>
                            </div>

                            <div className="form-group">
                                <label>Confirmer le nouveau mot de passe:</label>
                                <input
                                    type="password"
                                    value={confirmPassword}
                                    onChange={(e) => setConfirmPassword(e.target.value)}
                                    required
                                />
                            </div>

                            {message.text && (
                                <div className={`message ${message.type}`}>
                                    {message.text}
                                </div>
                            )}

                            <div className="form-actions">
                                <button
                                    type="button"
                                    className="cancel-btn"
                                    onClick={closeModal}
                                    disabled={loading}
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    className="submit-btn"
                                    disabled={loading}
                                >
                                    {loading ? 'Mise à jour...' : 'Mettre à jour'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </>
    );
};

export default ModifProfil;