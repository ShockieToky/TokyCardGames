import React from 'react';
import { useNavigate } from 'react-router-dom';
import '../styles/background.css';

const Background = () => {
    const navigate = useNavigate();

    return (
        <div className="background">
            <h1
                style={{ cursor: 'pointer' }}
                onClick={() => navigate('/Accueil')}
                title="Retour à l'accueil"
            >
                Toky Universe
            </h1>
        </div>
    );
};

export default Background;