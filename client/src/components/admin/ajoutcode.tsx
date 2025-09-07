import React, { useState, useEffect } from 'react';

const API_URL = 'http://localhost:8000';

interface Scroll {
    id: number;
    name: string;
}

const AjoutCode: React.FC = () => {
    const [name, setName] = useState('');
    const [expirationDate, setExpirationDate] = useState('');
    const [scrollId, setScrollId] = useState<number | ''>('');
    const [scrollCount, setScrollCount] = useState<number>(1);
    const [scrolls, setScrolls] = useState<Scroll[]>([]);
    const [message, setMessage] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);

    // Charger la liste des scrolls pour le choix
    useEffect(() => {
        const fetchScrolls = async () => {
            try {
                const res = await fetch(`${API_URL}/scrolls`, { credentials: 'include' });
                if (res.ok) {
                    setScrolls(await res.json());
                }
            } catch {
                setScrolls([]);
            }
        };
        fetchScrolls();
    }, []);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setMessage(null);

        if (!name || !expirationDate || !scrollId || !scrollCount) {
            setMessage('Tous les champs sont obligatoires.');
            return;
        }

        setLoading(true);
        try {
            const res = await fetch(`${API_URL}/code/create`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({
                    name,
                    expirationDate,
                    scrollId,
                    scrollCount,
                }),
            });
            const data = await res.json();
            if (data.success) {
                setMessage('Code ajouté avec succès !');
                setName('');
                setExpirationDate('');
                setScrollId('');
                setScrollCount(1);
            } else {
                setMessage(data.error || 'Erreur lors de l\'ajout du code.');
            }
        } catch {
            setMessage('Erreur réseau.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div style={{ maxWidth: 400, margin: '0 auto', background: '#fafafa', padding: 24, borderRadius: 12 }}>
            <h2>Ajouter un code</h2>
            <form onSubmit={handleSubmit}>
                <div style={{ marginBottom: 12 }}>
                    <label>Nom du code :</label>
                    <input
                        type="text"
                        value={name}
                        onChange={e => setName(e.target.value)}
                        required
                        style={{ width: '100%' }}
                        disabled={loading}
                    />
                </div>
                <div style={{ marginBottom: 12 }}>
                    <label>Date d'expiration :</label>
                    <input
                        type="date"
                        value={expirationDate}
                        onChange={e => setExpirationDate(e.target.value)}
                        required
                        style={{ width: '100%' }}
                        disabled={loading}
                    />
                </div>
                <div style={{ marginBottom: 12 }}>
                    <label>Récompense (scroll) :</label>
                    <select
                        value={scrollId}
                        onChange={e => setScrollId(Number(e.target.value))}
                        required
                        style={{ width: '100%' }}
                        disabled={loading}
                    >
                        <option value="">-- Choisir un scroll --</option>
                        {scrolls.map(scroll => (
                            <option key={scroll.id} value={scroll.id}>{scroll.name}</option>
                        ))}
                    </select>
                </div>
                <div style={{ marginBottom: 12 }}>
                    <label>Nombre de scrolls :</label>
                    <input
                        type="number"
                        min={1}
                        value={scrollCount}
                        onChange={e => setScrollCount(Number(e.target.value))}
                        required
                        style={{ width: '100%' }}
                        disabled={loading}
                    />
                </div>
                <button type="submit" disabled={loading} style={{ width: '100%' }}>
                    {loading ? 'Ajout en cours...' : 'Ajouter le code'}
                </button>
            </form>
            {message && (
                <div style={{ marginTop: 16, color: message.includes('succès') ? 'green' : 'red' }}>
                    {message}
                </div>
            )}
        </div>
    );
};

export default AjoutCode;