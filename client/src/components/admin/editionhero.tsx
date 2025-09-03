import * as React from "react";
import { useState } from "react";

const API_URL = 'http://localhost:8000/heroes/add';

const EditionHero = () => {
    const [form, setForm] = useState({
        name: "",
        HP: "",
        DEF: "",
        ATK: "",
        VIT: "",
        RES: "",
        star: "1",
        type: ""
    });
    const [message, setMessage] = useState<string | null>(null);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        setForm({ ...form, [e.target.name]: e.target.value });
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setMessage(null);

        const response = await fetch(API_URL, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "include",
            body: JSON.stringify({
                name: form.name,
                HP: Number(form.HP),
                DEF: Number(form.DEF),
                ATK: Number(form.ATK),
                VIT: Number(form.VIT),
                RES: Number(form.RES),
                star: Number(form.star),
                type: Number(form.type)
            })
        });

        const data = await response.json();
        if (data.success) {
            setMessage("Héros ajouté avec succès !");
            setForm({
                name: "",
                HP: "",
                DEF: "",
                ATK: "",
                VIT: "",
                RES: "",
                star: "1",
                type: ""
            });
        } else {
            setMessage(data.error || "Erreur lors de l'ajout du héros.");
        }
    };

    return (
        <div>
            <h1>Édition du Héros</h1>
            <form onSubmit={handleSubmit} style={{ display: "flex", flexDirection: "column", gap: "0.5rem", maxWidth: 300 }}>
                <input name="name" placeholder="Nom" value={form.name} onChange={handleChange} required />
                <input name="HP" type="number" placeholder="HP" value={form.HP} onChange={handleChange} required />
                <input name="DEF" type="number" placeholder="DEF" value={form.DEF} onChange={handleChange} required />
                <input name="ATK" type="number" placeholder="ATK" value={form.ATK} onChange={handleChange} required />
                <input name="VIT" type="number" placeholder="VIT" value={form.VIT} onChange={handleChange} required />
                <input name="RES" type="number" placeholder="RES" value={form.RES} onChange={handleChange} required />
                <select name="star" value={form.star} onChange={handleChange} required>
                    {[1, 2, 3, 4, 5, 6].map(n => (
                        <option key={n} value={n}>{'*'.repeat(n)}</option>
                    ))}
                </select>
                <select name="type" value={form.type} onChange={handleChange} required>
                    <option value="">Type du héros</option>
                    <option value={0}>PV</option>
                    <option value={1}>Défense</option>
                    <option value={2}>Attaque</option>
                </select>
                <button className="bouton-admin" type="submit">Ajouter le héros</button>
            </form>
            {message && <div style={{ marginTop: "1rem" }}>{message}</div>}
        </div>
    );
};

export default EditionHero;