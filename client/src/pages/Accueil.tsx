import React from 'react';
import Background from '../components/background';
import Redirection from '../components/accueil/redirection';
import Deconnexion from '../components/accueil/deconnexion';
import '../styles/accueil.css';
import ModifProfil from '../components/accueil/modifprofil';
import AffichageCollection from '../components/accueil/affichagecollection';


const Accueil = () => (
    <div>
        <Background />
        <Redirection />
        <AffichageCollection />
        <ModifProfil />
        <Deconnexion />
    </div>
);

export default Accueil;