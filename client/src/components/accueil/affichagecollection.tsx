import React, { useEffect, useState } from 'react';
import { useUser } from '../../context/UserContext';
import HeroPopup from './heropopup';

interface HeroCollectionEntry {
    heroId: number;
    heroName: string;
    star: number;
}

const API_URL = 'http://localhost:8000';

const PAGE_SIZE = 25;

const AffichageCollection: React.FC = () => {
    const { isLoggedIn } = useUser();
    const [collection, setCollection] = useState<HeroCollectionEntry[]>([]);
    const [loading, setLoading] = useState(true);
    const [page, setPage] = useState(1);
    const [popupHero, setPopupHero] = useState<{ heroId: number, heroName: string } | null>(null);

    useEffect(() => {
        const fetchCollection = async () => {
            setLoading(true);
            try {
                const response = await fetch(`${API_URL}/user/collection`, {
                    credentials: 'include'
                });
                if (response.ok) {
                    const data = await response.json();
                    setCollection(data);
                } else {
                    setCollection([]);
                }
            } catch {
                setCollection([]);
            } finally {
                setLoading(false);
            }
        };
        if (isLoggedIn) fetchCollection();
    }, [isLoggedIn]);

    // Pagination
    const totalPages = Math.ceil(collection.length / PAGE_SIZE);
    const paginated = collection.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);

    // Générer les lignes du tableau 5x5
    const rows = [];
    for (let i = 0; i < 5; i++) {
        rows.push(paginated.slice(i * 5, (i + 1) * 5));
    }

    return (
        <div className="collection-container">
            <h2>Ma collection</h2>
            {loading ? (
                <div>Chargement...</div>
            ) : (
                <>
                    <table style={{ borderCollapse: 'collapse', width: '100%', maxWidth: 600 }}>
                        <tbody>
                            {rows.map((row, i) => (
                                <tr key={i}>
                                    {row.map((hero, j) => (
                                        <td
                                            key={j}
                                            style={{
                                                border: '1px solid #ccc',
                                                padding: '12px',
                                                textAlign: 'center',
                                                minWidth: 100,
                                                minHeight: 60,
                                                cursor: hero ? 'pointer' : 'default',
                                                background: hero ? '#f8f8ff' : undefined
                                            }}
                                            onClick={() => hero && setPopupHero({ heroId: hero.heroId, heroName: hero.heroName })}
                                        >
                                            {hero ? (
                                                <>
                                                    <div style={{ fontWeight: 'bold' }}>{hero.heroName}</div>
                                                    <div>⭐{hero.star}</div>
                                                </>
                                            ) : ''}
                                        </td>
                                    ))}
                                    {row.length < 5 &&
                                        Array.from({ length: 5 - row.length }).map((_, k) => (
                                            <td key={`empty-${k}`} style={{ border: '1px solid #ccc' }} />
                                        ))}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {/* Pagination */}
                    {totalPages > 1 && (
                        <div style={{ marginTop: 16, display: 'flex', justifyContent: 'center', gap: 8 }}>
                            <button
                                onClick={() => setPage(page - 1)}
                                disabled={page === 1}
                            >
                                Précédent
                            </button>
                            <span>Page {page} / {totalPages}</span>
                            <button
                                onClick={() => setPage(page + 1)}
                                disabled={page === totalPages}
                            >
                                Suivant
                            </button>
                        </div>
                    )}
                    {popupHero && (
                        <HeroPopup
                            heroId={popupHero.heroId}
                            heroName={popupHero.heroName}
                            onClose={() => setPopupHero(null)}
                        />
                    )}
                </>
            )}
        </div>
    );
};

export default AffichageCollection;