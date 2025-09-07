import React from 'react';
import Background from '../components/background';
import Redirection from '../components/accueil/redirection';
import Deconnexion from '../components/accueil/deconnexion';
import '../styles/accueil.css';
import ModifProfil from '../components/accueil/modifprofil';
import AffichageCollection from '../components/accueil/affichagecollection';
import CodeInput from '../components/accueil/code';


const Accueil = () => (
    <div>
        <Background />
        <Redirection />
        <CodeInput />
        <AffichageCollection />
        <ModifProfil />
        <Deconnexion />
    </div>
);

export default Accueil;