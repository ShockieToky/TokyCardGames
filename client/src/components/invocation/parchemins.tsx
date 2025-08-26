import React, { useEffect, useState } from "react";

const SCROLLS_API = "http://localhost:8000/scrolls";
const USER_SCROLLS_API = "http://localhost:8000/user/scrolls";
const INVOKE_API = "http://localhost:8000/user/invoke";

type Props = {
    onSelectScroll: (id: number) => void;
    selectedScrollId: number | null;
};

const Parchemins: React.FC<Props> = ({ onSelectScroll, selectedScrollId }) => {
    const [scrolls, setScrolls] = useState<{ id: number, name: string, description: string }[]>([]);
    const [userScrolls, setUserScrolls] = useState<{ scrollId: number, quantity: number }[]>([]);
    const [loading, setLoading] = useState(true);
    const [message, setMessage] = useState<string | null>(null);
    const [summonedHero, setSummonedHero] = useState<{ name: string, star: number } | null>(null);

    // Récupère les parchemins et la collection utilisateur
    useEffect(() => {
        fetch(SCROLLS_API, { credentials: "include" })
            .then(res => res.json())
            .then(data => setScrolls(data))
            .catch(() => setScrolls([]));

        fetch(USER_SCROLLS_API, { credentials: "include" })
            .then(res => res.json())
            .then(data => setUserScrolls(data))
            .catch(() => setUserScrolls([]))
            .finally(() => setLoading(false));
    }, []);

    const userScrollsMap = Object.fromEntries(userScrolls.map(us => [us.scrollId, us.quantity]));

    // Fonction pour rafraîchir la collection utilisateur après invocation
    const refreshUserScrolls = () => {
        fetch(USER_SCROLLS_API, { credentials: "include" })
            .then(res => res.json())
            .then(data => setUserScrolls(data))
            .catch(() => setUserScrolls([]));
    };

    const handleInvoke = async (scrollId: number) => {
        setMessage(null);
        setSummonedHero(null);
        const response = await fetch(INVOKE_API, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "include",
            body: JSON.stringify({ scrollId })
        });
        const data = await response.json();
        if (data.success) {
            setMessage(`Héros invoqué : ${data.heroName} (${data.star}★)`);
            setSummonedHero({ name: data.heroName, star: data.star });
            refreshUserScrolls(); // Met à jour en temps réel la quantité de parchemins
        } else {
            setMessage(data.error || "Erreur lors de l'invocation.");
        }
    };

    if (loading) return <div>Chargement...</div>;

    return (
        <div>
            <h2>Parchemins disponibles</h2>
            <div style={{ display: "flex", flexWrap: "wrap", gap: "1rem" }}>
                {scrolls.map(scroll => {
                    const quantity = userScrollsMap[scroll.id] || 0;
                    const isSelected = selectedScrollId === scroll.id;
                    return (
                        <div
                            key={scroll.id}
                            style={{
                                border: isSelected ? "2px solid #3949ab" : "1px solid #ccc",
                                borderRadius: "8px",
                                padding: "1rem",
                                minWidth: "200px",
                                background: isSelected ? "#e3e6fd" : "#f9f9f9",
                                cursor: "pointer",
                                boxShadow: isSelected ? "0 0 8px #3949ab" : "none"
                            }}
                            onClick={() => onSelectScroll(scroll.id)}
                        >
                            <h3>{scroll.name}</h3>
                            <p>{scroll.description}</p>
                            <p>Parchemins disponibles : <strong>{quantity}</strong></p>
                            <button
                                disabled={quantity < 1}
                                onClick={e => {
                                    e.stopPropagation();
                                    handleInvoke(scroll.id);
                                }}
                            >
                                Invoquer x1
                            </button>
                        </div>
                    );
                })}
            </div>
            {message && <div style={{ marginTop: "1rem" }}>{message}</div>}
            {summonedHero && (
                <div style={{ marginTop: "1rem", padding: "1rem", border: "1px solid #3949ab", borderRadius: "8px", background: "#e3e6fd" }}>
                    <strong>Héros invoqué :</strong> {summonedHero.name} ({summonedHero.star}★)
                </div>
            )}
        </div>
    );
};

export default Parchemins;