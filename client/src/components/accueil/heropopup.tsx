import React, { useEffect, useState } from 'react';
import '../../styles/accueil.css';

const API_URL = 'http://localhost:8000';

interface HeroStats {
    HP: number;
    ATK: number;
    DEF: number;
    VIT: number;
    RES: number;
    star: number;
    type: string;
}

interface HeroSkill {
    id: number;
    name: string;
    description: string;
    multiplicator: number;
    scaling: string;
    hits_number: number;
    cooldown: number;
    initial_cooldown: number;
    is_passive: boolean;
    targeting: string;
    targeting_team: string;
    does_damage: boolean;
}

interface HeroPopupProps {
    heroId: number;
    heroName: string;
    onClose: () => void;
}

const HeroPopup: React.FC<HeroPopupProps> = ({ heroId, heroName, onClose }) => {
    const [stats, setStats] = useState<HeroStats | null>(null);
    const [skills, setSkills] = useState<HeroSkill[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchHero = async () => {
            setLoading(true);
            try {
                const resStats = await fetch(`${API_URL}/heroes/${heroId}`, { credentials: 'include' });
                const resSkills = await fetch(`${API_URL}/hero/${heroId}/skills`, { credentials: 'include' });
                if (resStats.ok && resSkills.ok) {
                    setStats(await resStats.json());
                    setSkills(await resSkills.json());
                }
            } catch { }
            setLoading(false);
        };
        fetchHero();
    }, [heroId]);

    if (!heroId) return null;

    return (
        <div className="hero-popup-overlay" onClick={onClose}>
            <div className="hero-popup-content" onClick={e => e.stopPropagation()}>
                <button className="hero-popup-close" onClick={onClose}>&times;</button>
                {loading ? (
                    <div>Chargement...</div>
                ) : stats ? (
                    <>
                        <h2>{heroName}</h2>
                        <div>
                            <strong>Étoiles :</strong> {stats.star} <br />
                            <strong>Type :</strong> {
                                (() => {
                                    const t = String(stats.type).trim();
                                    if (t === "0") return "PV";
                                    if (t === "1") return "Défense";
                                    if (t === "2") return "Attaque";
                                    return stats.type;
                                })()
                            } <br />
                            <strong>HP :</strong> {stats.HP} <br />
                            <strong>ATK :</strong> {stats.ATK} <br />
                            <strong>DEF :</strong> {stats.DEF} <br />
                            <strong>VIT :</strong> {stats.VIT} <br />
                            <strong>RES :</strong> {stats.RES}
                        </div>
                        <h3 style={{ marginTop: 16 }}>Sorts</h3>
                        <ul>
                            {skills.map(skill => (
                                <li key={skill.id} style={{ marginBottom: 12 }}>
                                    <strong>{skill.name}</strong> : {skill.description}
                                </li>
                            ))}
                        </ul>
                    </>
                ) : (
                    <div>Erreur lors du chargement du héros.</div>
                )}
            </div>
        </div>
    );
};

export default HeroPopup;