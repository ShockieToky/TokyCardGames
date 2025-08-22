
import React, { useState } from 'react';

// Nombre de héros par étoile
const heroesByStars = {
    1: 15,
    2: 25,
    3: 40,
    4: 30,
    5: 15,
    6: 6,
};

// Probabilités des parchemins
const scrollProbabilities = {
    basic: {
        stars: [1, 2],
        chances: [0.65, 0.35],
    },
    improved: {
        stars: [2, 3, 4],
        chances: [0.5, 0.35, 0.15],
    },
    legendary: {
        stars: [4, 5],
        chances: [0.8, 0.2],
    },
};

function drawStar(scrollType) {
    const { stars, chances } = scrollProbabilities[scrollType];
    const rand = Math.random();
    let acc = 0;
    for (let i = 0; i < stars.length; i++) {
        acc += chances[i];
        if (rand < acc) return stars[i];
    }
    return stars[stars.length - 1];
}

function drawHero(star) {
    return Math.floor(Math.random() * heroesByStars[star]) + 1;
}

const scrollTypes = [
    { key: 'basic', label: 'Parchemin de base' },
    { key: 'improved', label: 'Parchemin amélioré' },
    { key: 'legendary', label: 'Parchemin légendaire' },
];

const TestPack = () => {
    const [results, setResults] = useState([]);
    const [counter, setCounter] = useState(0);

    const openScrolls = (scrollType, count) => {
        const newResults = [];
        for (let i = 0; i < count; i++) {
            const star = drawStar(scrollType);
            const hero = drawHero(star);
            newResults.push({ star, hero, scroll: scrollType });
        }
        setResults(prev => [...prev, ...newResults]);
        setCounter(prev => prev + count);
    };

    const reset = () => {
        setResults([]);
        setCounter(0);
    };


    // Détection des doublons dans les résultats
    const heroCount = {};
    results.forEach(r => {
        const key = `${r.star}-${r.hero}`;
        heroCount[key] = (heroCount[key] || 0) + 1;
    });

    // Calcul du pourcentage de chaque étoile obtenue
    const starCounts = {};
    results.forEach(r => {
        starCounts[r.star] = (starCounts[r.star] || 0) + 1;
    });
    const total = results.length;
    const starPercentages = [1, 2, 3, 4, 5, 6].map(star => ({
        star,
        percent: total ? ((starCounts[star] || 0) / total * 100).toFixed(1) : '0.0'
    }));

    return (
        <div style={{ padding: 20 }}>
            <h2>Test Parchemins</h2>
            <div style={{ marginBottom: 12 }}>
                <strong>Pourcentage d'obtention par étoile :</strong>
                <ul style={{ display: 'flex', gap: 16, listStyle: 'none', paddingLeft: 0 }}>
                    {starPercentages.map(({ star, percent }) => (
                        <li key={star}>{star}★ : {percent}%</li>
                    ))}
                </ul>
            </div>
            <div style={{ marginBottom: 16 }}>
                {scrollTypes.map(scroll => (
                    <div key={scroll.key} style={{ marginBottom: 8 }}>
                        <button onClick={() => openScrolls(scroll.key, 1)}>
                            {scroll.label} x1
                        </button>
                        <button onClick={() => openScrolls(scroll.key, 10)} style={{ marginLeft: 8 }}>
                            {scroll.label} x10
                        </button>
                    </div>
                ))}
            </div>
            <div style={{ marginBottom: 16 }}>
                <button onClick={reset}>Reset</button>
            </div>
            <div>
                <strong>Compteur global : {counter}</strong>
            </div>
            <div style={{ marginTop: 16 }}>
                <h3>Résultats :</h3>
                {results.length === 0 ? (
                    <div>Aucun tirage effectué.</div>
                ) : (
                    <ul>
                        {results.map((r, idx) => {
                            const key = `${r.star}-${r.hero}`;
                            const isDuplicate = heroCount[key] > 1;
                            return (
                                <li key={idx} style={isDuplicate ? { color: 'red', fontWeight: 'bold' } : {}}>
                                    Parchemin : {r.scroll} — Héros {r.hero} ({r.star}★)
                                </li>
                            );
                        })}
                    </ul>
                )}
            </div>
        </div>
    );
};

export default TestPack;