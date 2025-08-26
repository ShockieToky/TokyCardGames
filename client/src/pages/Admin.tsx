import React, { useEffect, useState } from "react";
import EditionHero from "../components/admin/editionhero";

const Admin = () => {
    const [isAdmin, setIsAdmin] = useState<boolean | null>(null);

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

    return (
        <div>
            <h1>Admin Page</h1>
            <EditionHero />
        </div>
    );
};

export default Admin;