import React, { useState, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { useUser } from '../context/UserContext';
import '../styles/inscriptionconnexion.css';

const API_URL = 'http://localhost:8000/user';

interface FormState {
    pseudo: string;
    password: string;
    confirmPassword: string;
}

interface UIState {
    message: string | null;
    isSuccess: boolean;
    isLoading: boolean;
}

const InscriptionConnexion = () => {
    const [isRegister, setIsRegister] = useState(false);
    const [form, setForm] = useState<FormState>({
        pseudo: '',
        password: '',
        confirmPassword: ''
    });
    const [ui, setUI] = useState<UIState>({
        message: null,
        isSuccess: false,
        isLoading: false
    });

    const navigate = useNavigate();
    const { login } = useUser();

    // Gestion optimisée des changements de formulaire
    const updateForm = useCallback((field: keyof FormState, value: string) => {
        setForm(prev => ({ ...prev, [field]: value }));
    }, []);

    // Gestion optimisée de l'état UI
    const updateUI = useCallback((updates: Partial<UIState>) => {
        setUI(prev => ({ ...prev, ...updates }));
    }, []);

    // Validation centralisée
    const validateForm = useCallback((): string | null => {
        const { pseudo, password, confirmPassword } = form;

        if (!pseudo || !password || (isRegister && !confirmPassword)) {
            return 'Tous les champs sont requis.';
        }

        if (isRegister && password !== confirmPassword) {
            return 'Les mots de passe ne correspondent pas.';
        }

        return null;
    }, [form, isRegister]);

    // Gestion de l'inscription optimisée
    const handleRegister = useCallback(async () => {
        const response = await fetch(`${API_URL}/register`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pseudo: form.pseudo, password: form.password }),
            credentials: 'include',
        });

        const data = await response.json();

        if (data.success) {
            updateUI({ isSuccess: true, message: 'Inscription réussie !' });

            setTimeout(() => {
                setIsRegister(false);
                setForm({ pseudo: '', password: '', confirmPassword: '' });
                updateUI({
                    message: 'Inscription réussie ! Vous pouvez maintenant vous connecter.',
                    isSuccess: false
                });
            }, 1500);
        } else {
            updateUI({ message: data.error || 'Erreur lors de l\'inscription.' });
        }
    }, [form.pseudo, form.password, updateUI]);

    // Gestion de la connexion optimisée
    const handleLogin = useCallback(async () => {
        const result = await login(form.pseudo, form.password);

        if (result.success) {
            updateUI({ isSuccess: true, message: 'Connexion réussie !' });
            setTimeout(() => navigate('/Accueil'), 800);
        } else {
            updateUI({ message: result.error || 'Identifiants invalides.' });
        }
    }, [form.pseudo, form.password, login, navigate, updateUI]);

    // Soumission du formulaire optimisée
    const handleSubmit = useCallback(async (e: React.FormEvent) => {
        e.preventDefault();

        const validationError = validateForm();
        if (validationError) {
            updateUI({ message: validationError, isSuccess: false });
            return;
        }

        updateUI({ message: null, isSuccess: false, isLoading: true });

        try {
            await (isRegister ? handleRegister() : handleLogin());
        } catch (err) {
            console.error('Erreur:', err);
            updateUI({
                message: 'Erreur de connexion au serveur. Veuillez réessayer plus tard.'
            });
        } finally {
            updateUI({ isLoading: false });
        }
    }, [validateForm, isRegister, handleRegister, handleLogin, updateUI]);

    // Basculement de mode optimisé
    const switchForm = useCallback(() => {
        setIsRegister(prev => !prev);
        setForm({ pseudo: '', password: '', confirmPassword: '' });
        updateUI({ message: null, isSuccess: false });
    }, [updateUI]);

    const isDisabled = ui.isLoading || ui.isSuccess;

    return (
        <div className='formulaire'>
            <h2>{isRegister ? 'Inscription' : 'Connexion'}</h2>

            <form onSubmit={handleSubmit}>
                <div>
                    <input
                        type="text"
                        value={form.pseudo}
                        onChange={e => updateForm('pseudo', e.target.value)}
                        required
                        placeholder='pseudo'
                        autoComplete="username"
                        disabled={isDisabled}
                    />
                </div>

                <div>
                    <input
                        type="password"
                        value={form.password}
                        onChange={e => updateForm('password', e.target.value)}
                        required
                        placeholder='mot de passe'
                        autoComplete={isRegister ? "new-password" : "current-password"}
                        disabled={isDisabled}
                    />
                </div>

                {isRegister && (
                    <div>
                        <input
                            type="password"
                            value={form.confirmPassword}
                            onChange={e => updateForm('confirmPassword', e.target.value)}
                            required
                            placeholder='confirmer mot de passe'
                            autoComplete="new-password"
                            disabled={isDisabled}
                        />
                    </div>
                )}

                <button
                    className='submit-button'
                    type="submit"
                    disabled={isDisabled}
                >
                    {ui.isLoading ? 'Chargement...' : isRegister ? "S'inscrire" : 'Se connecter'}
                </button>
            </form>

            <button
                className='switch-button'
                onClick={switchForm}
                disabled={isDisabled}
            >
                {isRegister ? 'Déjà inscrit ? Se connecter' : "Pas encore inscrit ? S'inscrire"}
            </button>

            {ui.message && (
                <div className={`message ${ui.isSuccess ? 'success' : 'error'}`}>
                    {ui.message}
                </div>
            )}
        </div>
    );
};

export default InscriptionConnexion;