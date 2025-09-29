import React, { useEffect, useState } from "react";
import EditionHero from "../components/admin/editionhero";
import EditionParchemin from "../components/admin/editionparchemin";
import AjoutUtilisateurs from "../components/admin/ajoututilisateurs";
import ModifHero from "../components/admin/modifhero";
import AjoutCode from "../components/admin/ajoutcode";
import CreationEffet from "../components/admin/creationeffet";
import '../styles/admin.css';

const Admin = () => {
    const [isAdmin, setIsAdmin] = useState<boolean | null>(null);
    const [selectedComponent, setSelectedComponent] = useState<string>("modifhero");

    useEffect(() => {
        fetch("http://localhost:8000/user/me", {
            method: "GET",
            credentials: "include"
        })
            .then(res => res.json())
            .then(data => {
                setIsAdmin(data.success ? !!data.isAdmin : false);
            })
            .catch(() => setIsAdmin(false));
    }, []);

    if (isAdmin === null) return <div>Chargement...</div>;
    if (!isAdmin) return <div>Accès refusé : vous n'êtes pas administrateur.</div>;

    // Fonction pour rendre le composant sélectionné
    const renderComponent = () => {
        switch (selectedComponent) {
            case "editionhero":
                return <EditionHero />;
            case "editionparchemin":
                return <EditionParchemin />;
            case "ajoututilisateurs":
                return <AjoutUtilisateurs />;
            case "modifhero":
                return <ModifHero />;
            case "ajoutcode":
                return <AjoutCode />;
            case "creationeffet":
                return <CreationEffet />;
            default:
                return <div>Sélectionnez un composant</div>;
        }
    };

    return (
        <div className="admin-page">
            <h1>Administration</h1>

            <div className="admin-selector">
                <label htmlFor="component-select">Choisir une action :</label>
                <select
                    id="component-select"
                    value={selectedComponent}
                    onChange={(e) => setSelectedComponent(e.target.value)}
                >
                    <option value="modifhero">Modifier un héros</option>
                    <option value="editionhero">Ajouter un héros</option>
                    <option value="editionparchemin">Gérer les parchemins</option>
                    <option value="ajoututilisateurs">Gérer les utilisateurs</option>
                    <option value="ajoutcode">Ajouter un code</option>
                    <option value="creationeffet">Créer un effet</option>
                </select>
            </div>

            <div className="admin-content">
                {renderComponent()}
            </div>
        </div>
    );
};

export default Admin;