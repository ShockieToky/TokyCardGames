import React, { useState } from 'react';

const API_URL = 'http://localhost:8000/user';

const InscriptionConnexion = () => {
    const [isRegister, setIsRegister] = useState(false);
    const [pseudo, setPseudo] = useState('');
    const [password, setPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const [message, setMessage] = useState<string | null>(null);

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
            });
            const data = await response.json();
            if (data.success) {
                setMessage(isRegister ? 'Inscription réussie !' : 'Connexion réussie !');
                // Ici tu peux gérer la redirection ou le stockage du userId
            } else {
                setMessage(data.error || 'Erreur inconnue.');
            }
        } catch (err) {
            setMessage('Erreur de connexion au serveur.');
        }
    };

    return (
        <div style={{ maxWidth: 350, margin: 'auto', padding: 20, border: '1px solid #ccc', borderRadius: 8 }}>
            <h2>{isRegister ? 'Inscription' : 'Connexion'}</h2>
            <form onSubmit={handleSubmit}>
                <div>
                    <label>Pseudo :</label>
                    <input
                        type="text"
                        value={pseudo}
                        onChange={e => setPseudo(e.target.value)}
                        required
                        autoComplete="username"
                    />
                </div>
                <div>
                    <label>Mot de passe :</label>
                    <input
                        type="password"
                        value={password}
                        onChange={e => setPassword(e.target.value)}
                        required
                        autoComplete={isRegister ? "new-password" : "current-password"}
                    />
                </div>
                {isRegister && (
                    <div>
                        <label>Confirmer le mot de passe :</label>
                        <input
                            type="password"
                            value={confirmPassword}
                            onChange={e => setConfirmPassword(e.target.value)}
                            required
                            autoComplete="new-password"
                        />
                    </div>
                )}
                <button type="submit" style={{ marginTop: 10 }}>
                    {isRegister ? "S'inscrire" : 'Se connecter'}
                </button>
            </form>
            <button
                style={{ marginTop: 10 }}
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