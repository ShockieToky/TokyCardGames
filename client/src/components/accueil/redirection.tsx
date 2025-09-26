import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';

const API_URL = 'http://localhost:8000/user/me';

const Redirection: React.FC = () => {
    const navigate = useNavigate();
    const [isAdmin, setIsAdmin] = useState(false);

    useEffect(() => {
        fetch(API_URL, {
            method: 'GET',
            credentials: 'include'
        })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.isAdmin) {
                    setIsAdmin(true);
                } else {
                    setIsAdmin(false);
                }
            })
            .catch(() => setIsAdmin(false));
    }, []);

    // Gestion du tooltip "pas encore dispo"
    const notAvailable = (e: React.MouseEvent<HTMLButtonElement>) => {
        e.preventDefault();
    };

    return (
        <div className='redirection-container'>
            <button
                className='bouton-redirect-disabled'
                onClick={notAvailable}
                style={{ cursor: 'not-allowed' }}
            >
                Arène PVE (pas encore dispo)
            </button>
            <button
                className='bouton-redirect-disabled'
                onClick={notAvailable}
                style={{ cursor: 'not-allowed' }}
            >
                Arène PVP (pas encore dispo)
            </button>
            <button
                className='bouton-redirect-disabled'
                onClick={notAvailable}
                style={{ cursor: 'not-allowed' }}
            >
                Mode Histoire (pas encore dispo)
            </button>
            <button
                className='bouton-redirect-disabled'
                onClick={notAvailable}
                style={{ cursor: 'not-allowed' }}
            >
                Mode Donjons (pas encore dispo)
            </button>
            <button
                className='bouton-redirect'
                onClick={() => navigate('/Invocation')}
            >
                Invocation
            </button>
            <button
                className='bouton-redirect'
                onClick={() => navigate('/ZoneCombat')}
            >
                Zone de test combat
            </button>
            {isAdmin && (
                <button
                    className='bouton-redirect'
                    onClick={() => navigate('/Admin')}
                >
                    Page Admin
                </button>
            )}
        </div>
    );
};

export default Redirection;