import React from 'react';
import Background from '../components/background';
import InscriptionConnexion from '../components/inscriptionconnexion';

const Form = () => {
    return (
        <div>
            <Background />
            <InscriptionConnexion onLogin={onLogin} />
        </div>
    );
};

export default Form;