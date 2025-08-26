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

    return (
        <div className='redirection-container'>
            {/* <button onClick={() => navigate('/arene-pve')}>Arène PVE</button>
            <button onClick={() => navigate('/arene-pvp')}>Arène PVP</button>
            <button onClick={() => navigate('/histoire')}>Mode Histoire</button>
            <button onClick={() => navigate('/donjons')}>Mode Donjons</button> */}
            <button onClick={() => navigate('/Invocation')}>Invocation</button>
            {isAdmin && (
                <button onClick={() => navigate('/Admin')}>Page Admin</button>
            )}
        </div>
    );
};

export default Redirection;