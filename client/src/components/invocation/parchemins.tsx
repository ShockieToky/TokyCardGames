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
    const [messageByScroll, setMessageByScroll] = useState<{ [key: number]: string }>({});
    const [summonedHeroByScroll, setSummonedHeroByScroll] = useState<{ [key: number]: { name: string, star: number }[] }>({});

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

    // Rafraîchit la collection utilisateur après invocation
    const refreshUserScrolls = () => {
        fetch(USER_SCROLLS_API, { credentials: "include" })
            .then(res => res.json())
            .then(data => setUserScrolls(data))
            .catch(() => setUserScrolls([]));
    };

    const handleInvoke = async (scrollId: number, count: number = 1) => {
        setMessageByScroll(prev => ({ ...prev, [scrollId]: "" }));
        setSummonedHeroByScroll(prev => ({ ...prev, [scrollId]: [] }));

        const response = await fetch(INVOKE_API, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "include",
            body: JSON.stringify({ scrollId, count })
        });
        const data = await response.json();
        if (data.success) {
            if (count === 1) {
                setMessageByScroll(prev => ({
                    ...prev,
                    [scrollId]: `Héros invoqué : ${data.heroName} (${data.star}★)`
                }));
                setSummonedHeroByScroll(prev => ({
                    ...prev,
                    [scrollId]: [{ name: data.heroName, star: data.star }]
                }));
            } else {
                setMessageByScroll(prev => ({
                    ...prev,
                    [scrollId]: `Héros invoqués :`
                }));
                setSummonedHeroByScroll(prev => ({
                    ...prev,
                    [scrollId]: Array.isArray(data.heroes)
                        ? data.heroes.map((h: any) => ({ name: h.heroName, star: h.star }))
                        : []
                }));
            }
            refreshUserScrolls();
        } else {
            setMessageByScroll(prev => ({
                ...prev,
                [scrollId]: data.error || "Erreur lors de l'invocation."
            }));
            setSummonedHeroByScroll(prev => ({ ...prev, [scrollId]: [] }));
        }
    };

    if (loading) return <div>Chargement...</div>;

    return (
        <div>
            <h2>Parchemins disponibles</h2>
            <div className="parchemins-container">
                {scrolls.map(scroll => {
                    const quantity = userScrollsMap[scroll.id] || 0;
                    const isSelected = selectedScrollId === scroll.id;
                    return (
                        <div
                            className={`parchemin${isSelected ? " selected" : ""}`}
                            key={scroll.id}
                            onClick={() => onSelectScroll(scroll.id)}
                        >
                            <h3>{scroll.name}</h3>
                            <p>{scroll.description}</p>
                            <p>Parchemins disponibles : <strong>{quantity}</strong></p>
                            <button
                                disabled={quantity < 1}
                                onClick={e => {
                                    e.stopPropagation();
                                    handleInvoke(scroll.id, 1);
                                }}
                            >
                                Invoquer x1
                            </button>
                            <button
                                disabled={quantity < 10}
                                style={{ marginLeft: 8 }}
                                onClick={e => {
                                    e.stopPropagation();
                                    handleInvoke(scroll.id, 10);
                                }}
                            >
                                Invoquer x10
                            </button>
                            {isSelected && (
                                <>
                                    {messageByScroll[scroll.id] && (
                                        <div style={{ marginTop: "1rem" }}>{messageByScroll[scroll.id]}</div>
                                    )}
                                    {summonedHeroByScroll[scroll.id] && summonedHeroByScroll[scroll.id].length > 0 && (
                                        <div style={{
                                            marginTop: "1rem",
                                            padding: "1rem",
                                            border: "1px solid #3949ab",
                                            borderRadius: "8px",
                                            background: "#e3e6fd"
                                        }}>
                                            {summonedHeroByScroll[scroll.id].map((hero, idx) => (
                                                <div key={idx}>
                                                    <strong>{hero.name}</strong> ({hero.star}★)
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </>
                            )}
                        </div>
                    );
                })}
            </div>
        </div>
    );
};

export default Parchemins;