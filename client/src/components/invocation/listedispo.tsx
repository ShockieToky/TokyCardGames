import React, { useEffect, useState } from "react";

const HEROES_API = "http://localhost:8000/heroes";
const USER_COLLECTION_API = "http://localhost:8000/user/collection";

type Props = {
    scrollId: number | null;
};

const ListeDispo: React.FC<Props> = ({ scrollId }) => {
    const [heroes, setHeroes] = useState<{ id: number, name: string, star: number }[]>([]);
    const [loading, setLoading] = useState(false);
    const [ownedHeroIds, setOwnedHeroIds] = useState<Set<number>>(new Set());

    useEffect(() => {
        // Récupère la collection de l'utilisateur
        fetch(USER_COLLECTION_API, { credentials: "include" })
            .then(res => res.json())
            .then(collection => {
                // On stocke tous les IDs des héros possédés (doublons possibles, on s'en fiche)
                setOwnedHeroIds(new Set(collection.map((h: any) => h.heroId)));
            })
            .catch(() => setOwnedHeroIds(new Set()));
    }, []);

    useEffect(() => {
        if (!scrollId) {
            setHeroes([]);
            return;
        }
        setLoading(true);
        fetch(`http://localhost:8000/scroll/rate/${scrollId}`, { credentials: "include" })
            .then(res => res.json())
            .then(rateData => {
                if (!rateData.rates) {
                    setHeroes([]);
                    setLoading(false);
                    return;
                }
                fetch(HEROES_API, { credentials: "include" })
                    .then(res => res.json())
                    .then(heroData => {
                        const stars = rateData.rates
                            .filter((r: any) => Number(r.rate) > 0)
                            .map((r: any) => Number(r.star));
                        const filtered = heroData.filter((h: any) => stars.includes(Number(h.star)));
                        setHeroes(filtered);
                        setLoading(false);
                    })
                    .catch(() => {
                        setHeroes([]);
                        setLoading(false);
                    });
            })
            .catch(() => {
                setHeroes([]);
                setLoading(false);
            });
    }, [scrollId]);

    if (!scrollId) return null;
    if (loading) return <div>Chargement des héros...</div>;

    return (
        <div className="liste-dispo">
            <h4>Héros disponibles avec ce parchemin :</h4>
            {heroes.length === 0 ? (
                <div>Aucun héros disponible.</div>
            ) : (
                <ul>
                    {heroes.map(hero => (
                        <li key={hero.id}>
                            <span style={{ color: ownedHeroIds.has(hero.id) ? "red" : undefined }}>
                                {hero.name}
                            </span>{" "}
                            <span style={{ color: "#3949ab" }}>({hero.star}★)</span>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
};

export default ListeDispo;