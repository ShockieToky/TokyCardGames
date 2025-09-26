import React from "react";
import Background from "../components/background";
import ChoixEquipe from "../components/zone test combat/choixequipe";
import ZoneCombat from "../components/zone test combat/zonecombat";

const TestCombat = () => {
    return (
        <div>
            <Background />
            <ChoixEquipe />
            <ZoneCombat
                teamA={[]}
                teamB={[]}
                onBackToSelection={() => { }}
            />
        </div>
    );
};

export default TestCombat;
