import React from 'react';
import Background from '../components/background';
import Redirection from '../components/accueil/redirection';
import Deconnexion from '../components/accueil/deconnexion';
import '../styles/accueil.css';
import ModifProfil from '../components/accueil/modifprofil';


const Accueil = () => (
    <div>
        <Background />
        <Redirection />
        <ModifProfil />
        <Deconnexion />
    </div>
);

export default Accueil;