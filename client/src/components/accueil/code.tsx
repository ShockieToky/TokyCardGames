import React, { useState } from 'react';

const API_URL = 'http://localhost:8000';

const CodeInput: React.FC = () => {
    const [code, setCode] = useState('');
    const [message, setMessage] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setMessage(null);
        if (!code.trim()) {
            setMessage('Veuillez entrer un code.');
            return;
        }
        setLoading(true);
        try {
            const res = await fetch(`${API_URL}/user/code/claim`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ code: code.trim() }),
            });
            const data = await res.json();
            if (data.success) {
                setMessage('Récompense récupérée !');
                setCode('');
            } else {
                setMessage(data.error || 'Code invalide ou déjà utilisé.');
            }
        } catch {
            setMessage('Erreur réseau.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <form className="code-input-form" onSubmit={handleSubmit}>
            <input
                type="text"
                placeholder="Entrer un code"
                value={code}
                onChange={e => setCode(e.target.value)}
                disabled={loading}
            />
            <button type="submit" disabled={loading}>
                Valider
            </button>
            {message && (
                <span style={{ color: message.includes('Récompense') ? 'green' : 'red' }}>
                    {message}
                </span>
            )}
        </form>
    );
};

export default CodeInput;