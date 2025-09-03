import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';

const API_URL = 'http://localhost:8000';

const Deconnexion: React.FC = () => {
    const navigate = useNavigate();
    const [loading, setLoading] = useState(false);

    const handleLogout = async () => {
        // Correction: utiliser window.confirm au lieu de confirm directement
        if (!window.confirm("Êtes-vous sûr de vouloir vous déconnecter ?")) {
            return;
        }

        setLoading(true);

        try {
            const response = await fetch(`${API_URL}/logout`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (response.ok) {
                navigate('/');
                window.location.reload();
            } else {
                console.error('Erreur lors de la déconnexion:', await response.text());
                alert('Erreur lors de la déconnexion. Veuillez réessayer.');
            }
        } catch (error) {
            console.error('Erreur lors de la déconnexion:', error);
            alert('Erreur réseau lors de la déconnexion. Veuillez réessayer.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <button
            onClick={handleLogout}
            disabled={loading}
            className="logout-button"
        >
            {loading ? 'Déconnexion...' : 'Se déconnecter'}
        </button>
    );
};

export default Deconnexion;