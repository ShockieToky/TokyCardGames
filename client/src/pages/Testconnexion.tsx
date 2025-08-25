import React, { useEffect, useState } from 'react';

const API_URL = 'http://localhost:8000/user';

const Testconnexion = () => {
    const [pseudo, setPseudo] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        // On utilise la session côté back, donc on ne lit plus le localStorage
        fetch(`${API_URL}/me`, {
            method: 'GET',
            credentials: 'include'
        })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.pseudo) {
                    setPseudo(data.pseudo);
                } else {
                    setPseudo(null);
                }
                setLoading(false);
            })
            .catch(() => {
                setPseudo(null);
                setLoading(false);
            });
    }, []);

    if (loading) return <div>Chargement...</div>;
    if (!pseudo) return <div>Vous n'êtes pas connecté.</div>;

    return (
        <div>
            <h2>Bienvenue, {pseudo} !</h2>
            <p>Félicitations, vous êtes connecté.</p>
        </div>
    );
};

export default Testconnexion;