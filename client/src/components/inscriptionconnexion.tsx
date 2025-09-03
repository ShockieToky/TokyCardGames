import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import '../styles/inscriptionconnexion.css';

const API_URL = 'http://localhost:8000/user';

const InscriptionConnexion = () => {
    const [isRegister, setIsRegister] = useState(false);
    const [pseudo, setPseudo] = useState('');
    const [password, setPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const [message, setMessage] = useState<string | null>(null);
    const [isSuccess, setIsSuccess] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const navigate = useNavigate();

    const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        setMessage(null);
        setIsSuccess(false);
        setIsLoading(true);

        // Validation des entrées
        if (!pseudo || !password || (isRegister && !confirmPassword)) {
            setMessage('Tous les champs sont requis.');
            setIsLoading(false);
            return;
        }

        if (isRegister && password !== confirmPassword) {
            setMessage('Les mots de passe ne correspondent pas.');
            setIsLoading(false);
            return;
        }

        try {
            console.log(`Envoi de la requête ${isRegister ? 'inscription' : 'connexion'}...`);

            const response = await fetch(`${API_URL}/${isRegister ? 'register' : 'login'}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    pseudo,
                    password,
                }),
                credentials: 'include',
            });

            console.log('Réponse reçue:', response.status);

            const data = await response.json();
            console.log('Données reçues:', data);

            if (data.success) {
                setIsSuccess(true);
                setMessage(isRegister ? 'Inscription réussie !' : 'Connexion réussie !');

                if (data.userId) {
                    // Stocker l'ID utilisateur localement si nécessaire
                    localStorage.setItem('userId', data.userId.toString());

                    if (data.isAdmin) {
                        localStorage.setItem('isAdmin', 'true');
                    }
                }

                // Si c'est une connexion, rafraîchir la page pour forcer la vérification de session
                if (!isRegister) {
                    console.log('Rafraîchissement de la page pour actualiser la session...');

                    // Courte temporisation pour afficher le message de succès
                    setTimeout(() => {
                        window.location.href = '/Accueil'; // Redirection directe via URL
                    }, 800);
                } else {
                    // Si c'est une inscription, attendre puis basculer vers le formulaire de connexion
                    setTimeout(() => {
                        setIsRegister(false);
                        setPseudo('');
                        setPassword('');
                        setConfirmPassword('');
                        setMessage('Inscription réussie ! Vous pouvez maintenant vous connecter.');
                        setIsSuccess(false);
                    }, 1500);
                }
            } else {
                // Message d'erreur du serveur
                setMessage(data.error || 'Erreur inconnue.');
            }
        } catch (err) {
            console.error('Erreur lors de la requête:', err);
            setMessage('Erreur de connexion au serveur. Veuillez réessayer plus tard.');
        } finally {
            setIsLoading(false);
        }
    };

    const switchForm = () => {
        setIsRegister(!isRegister);
        setMessage(null);
        setPseudo('');
        setPassword('');
        setConfirmPassword('');
    };

    return (
        <div className='formulaire'>
            <h2>{isRegister ? 'Inscription' : 'Connexion'}</h2>
            <form onSubmit={handleSubmit}>
                <div>
                    <input
                        type="text"
                        value={pseudo}
                        onChange={e => setPseudo(e.target.value)}
                        required
                        placeholder='pseudo'
                        autoComplete="username"
                        disabled={isLoading || isSuccess}
                    />
                </div>
                <div>
                    <input
                        type="password"
                        value={password}
                        onChange={e => setPassword(e.target.value)}
                        required
                        placeholder='mot de passe'
                        autoComplete={isRegister ? "new-password" : "current-password"}
                        disabled={isLoading || isSuccess}
                    />
                </div>
                {isRegister && (
                    <div>
                        <input
                            type="password"
                            value={confirmPassword}
                            onChange={e => setConfirmPassword(e.target.value)}
                            required
                            placeholder='confirmer mot de passe'
                            autoComplete="new-password"
                            disabled={isLoading || isSuccess}
                        />
                    </div>
                )}
                <button
                    className='submit-button'
                    type="submit"
                    disabled={isLoading || isSuccess}
                >
                    {isLoading ? 'Chargement...' : isRegister ? "S'inscrire" : 'Se connecter'}
                </button>
            </form>
            <button
                className='switch-button'
                onClick={switchForm}
                disabled={isLoading || isSuccess}
            >
                {isRegister ? 'Déjà inscrit ? Se connecter' : "Pas encore inscrit ? S'inscrire"}
            </button>

            {message && (
                <div className={`message ${isSuccess ? 'success' : 'error'}`}>
                    {message}
                </div>
            )}
        </div>
    );
};

export default InscriptionConnexion;