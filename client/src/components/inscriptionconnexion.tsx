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
    const navigate = useNavigate();

    const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        setMessage(null);

        if (!pseudo || !password || (isRegister && !confirmPassword)) {
            setMessage('Tous les champs sont requis.');
            return;
        }

        if (isRegister && password !== confirmPassword) {
            setMessage('Les mots de passe ne correspondent pas.');
            return;
        }

        try {
            const response = await fetch(`${API_URL}/${isRegister ? 'register' : 'login'}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    pseudo,
                    password,
                }),
                credentials: 'include',
            });
            const data = await response.json();
            if (data.success) {
                setMessage(isRegister ? 'Inscription réussie !' : 'Connexion réussie !');
                if (data.userId) {
                    localStorage.setItem('userId', data.userId);
                }
                setTimeout(() => {
                    navigate('/Accueil');
                }, 800);
            } else {
                setMessage(data.error || 'Erreur inconnue.');
            }
        } catch (err) {
            setMessage('Erreur de connexion au serveur.');
        }
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
                        />
                    </div>
                )}
                <button className='submit-button' type="submit">
                    {isRegister ? "S'inscrire" : 'Se connecter'}
                </button>
            </form>
            <button
                className='switch-button'
                onClick={() => {
                    setIsRegister(!isRegister);
                    setMessage(null);
                }}
            >
                {isRegister ? 'Déjà inscrit ? Se connecter' : "Pas encore inscrit ? S'inscrire"}
            </button>
            {message && <div style={{ marginTop: 15, color: 'red' }}>{message}</div>}
        </div>
    );
};

export default InscriptionConnexion;