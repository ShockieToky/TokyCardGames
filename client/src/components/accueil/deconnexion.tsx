import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useUser } from '../../context/UserContext';

const Deconnexion: React.FC = () => {
    const navigate = useNavigate();
    const [loading, setLoading] = useState(false);
    const { logout } = useUser();

    const handleLogout = async () => {
        if (!window.confirm("Êtes-vous sûr de vouloir vous déconnecter ?")) {
            return;
        }

        setLoading(true);

        try {
            await logout();
            navigate('/');
        } catch (error) {
            console.error('Erreur lors de la déconnexion:', error);
            alert('Erreur lors de la déconnexion. Veuillez réessayer.');
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