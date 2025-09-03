import React, { useState } from "react";

const API_URL = "http://localhost:8000/scrolls/add";
const RATE_URL = "http://localhost:8000/scroll/rate";

const EditionParchemin = () => {
    const [form, setForm] = useState({
        name: "",
        description: ""
    });
    const [rates, setRates] = useState([
        { star: 1, rate: 0 },
        { star: 2, rate: 0 },
        { star: 3, rate: 0 },
        { star: 4, rate: 0 },
        { star: 5, rate: 0 },
        { star: 6, rate: 0 }
    ]);
    const [message, setMessage] = useState<string | null>(null);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
        setForm({ ...form, [e.target.name]: e.target.value });
    };

    const handleRateChange = (index: number, value: string) => {
        const newRates = [...rates];
        newRates[index].rate = parseFloat(value);
        setRates(newRates);
    };

    const totalRate = rates.reduce((sum, r) => sum + r.rate, 0);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setMessage(null);

        if (!form.name || !form.description) {
            setMessage("Veuillez remplir tous les champs.");
            return;
        }

        if (Math.abs(totalRate - 1) > 0.0001) {
            setMessage("Le total des probabilités doit être exactement égal à 1.");
            return;
        }

        // 1. Création du parchemin
        const response = await fetch(API_URL, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "include",
            body: JSON.stringify({
                name: form.name,
                description: form.description
            })
        });

        const data = await response.json();
        if (!data.success || !data.id) {
            setMessage(data.error || "Erreur lors de l'ajout du parchemin.");
            return;
        }

        // 2. Création des rates
        const rateResponse = await fetch(`${RATE_URL}/${data.id}/set`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "include",
            body: JSON.stringify({ rates })
        });

        const rateData = await rateResponse.json();
        if (rateData.success) {
            setMessage("Parchemin et probabilités ajoutés avec succès !");
            setForm({ name: "", description: "" });
            setRates([
                { star: 1, rate: 0 },
                { star: 2, rate: 0 },
                { star: 3, rate: 0 },
                { star: 4, rate: 0 },
                { star: 5, rate: 0 },
                { star: 6, rate: 0 }
            ]);
        } else {
            setMessage(rateData.error || "Erreur lors de l'ajout des probabilités.");
        }
    };

    return (
        <div>
            <h1>Ajouter un parchemin</h1>
            <form onSubmit={handleSubmit} style={{ display: "flex", flexDirection: "column", gap: "0.5rem", maxWidth: 300 }}>
                <input
                    name="name"
                    placeholder="Nom du parchemin"
                    value={form.name}
                    onChange={handleChange}
                    required
                />
                <textarea
                    name="description"
                    placeholder="Description"
                    value={form.description}
                    onChange={handleChange}
                    required
                    rows={4}
                />
                <h2>Probabilités par étoile</h2>
                {rates.map((r, idx) => (
                    <div key={r.star} style={{ display: "flex", alignItems: "center", gap: "0.5rem" }}>
                        <span>{r.star} étoile{r.star > 1 ? "s" : ""} :</span>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            max="1"
                            value={r.rate}
                            onChange={e => handleRateChange(idx, e.target.value)}
                            required
                        />
                    </div>
                ))}
                <div>Total : {totalRate.toFixed(2)}</div>
                <button className="bouton-admin" type="submit">Ajouter le parchemin</button>
            </form>
            {message && <div style={{ marginTop: "1rem" }}>{message}</div>}
        </div>
    );
};

export default EditionParchemin;