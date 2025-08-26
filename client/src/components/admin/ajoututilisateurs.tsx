import React, { useEffect, useState } from "react";

const USERS_API = "http://localhost:8000/users"; // à créer : GET tous les users
const SCROLLS_API = "http://localhost:8000/scrolls"; // GET tous les parchemins
const ADD_SCROLL_API = "http://localhost:8000/user/scrolls/add";

const AjoutUtilisateurs = () => {
    const [users, setUsers] = useState<{ id: number, pseudo: string }[]>([]);
    const [scrolls, setScrolls] = useState<{ id: number, name: string }[]>([]);
    const [selectedUser, setSelectedUser] = useState<string>("all");
    const [selectedScroll, setSelectedScroll] = useState<string>("");
    const [quantity, setQuantity] = useState<number>(1);
    const [message, setMessage] = useState<string | null>(null);

    useEffect(() => {
        // Récupère la liste des utilisateurs
        fetch(USERS_API, { credentials: "include" })
            .then(res => res.json())
            .then(data => setUsers(data))
            .catch(() => setUsers([]));

        // Récupère la liste des parchemins
        fetch(SCROLLS_API, { credentials: "include" })
            .then(res => res.json())
            .then(data => setScrolls(data))
            .catch(() => setScrolls([]));
    }, []);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setMessage(null);

        if (!selectedScroll || quantity < 1) {
            setMessage("Veuillez choisir un parchemin et une quantité valide.");
            return;
        }

        let success = true;
        let errorMsg = "";

        // Ajout pour tous les utilisateurs
        const targets = selectedUser === "all" ? users.map(u => u.id) : [selectedUser];

        for (const userId of targets) {
            const response = await fetch(ADD_SCROLL_API, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                credentials: "include",
                body: JSON.stringify({
                    userId,
                    scrollId: Number(selectedScroll),
                    quantity: Number(quantity)
                })
            });
            const data = await response.json();
            if (!data.success) {
                success = false;
                errorMsg = data.error || "Erreur lors de l'ajout.";
                break;
            }
        }

        setMessage(success ? "Ajout effectué !" : errorMsg);
    };

    return (
        <div>
            <h2>Ajouter des parchemins à un utilisateur</h2>
            <form onSubmit={handleSubmit} style={{ display: "flex", flexDirection: "column", gap: "0.5rem", maxWidth: 350 }}>
                <label>
                    Utilisateur :
                    <select value={selectedUser} onChange={e => setSelectedUser(e.target.value)}>
                        <option value="all">Tous les utilisateurs</option>
                        {users.map(user => (
                            <option key={user.id} value={user.id}>{user.pseudo}</option>
                        ))}
                    </select>
                </label>
                <label>
                    Parchemin :
                    <select value={selectedScroll} onChange={e => setSelectedScroll(e.target.value)} required>
                        <option value="">Choisir un parchemin</option>
                        {scrolls.map(scroll => (
                            <option key={scroll.id} value={scroll.id}>{scroll.name}</option>
                        ))}
                    </select>
                </label>
                <label>
                    Quantité :
                    <input
                        type="number"
                        min={1}
                        value={quantity}
                        onChange={e => setQuantity(Number(e.target.value))}
                        required
                    />
                </label>
                <button type="submit">Ajouter</button>
            </form>
            {message && <div style={{ marginTop: "1rem" }}>{message}</div>}
        </div>
    );
};

export default AjoutUtilisateurs;