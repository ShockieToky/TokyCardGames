import React from "react";
import Parchemins from "../components/invocation/parchemins";
import ListeDispo from "../components/invocation/listedispo";
import '../styles/invocation.css';
import Background from "../components/background";

const Invocation: React.FC = () => {
    const [selectedScrollId, setSelectedScrollId] = React.useState<number | null>(null);

    return (
        <div>
            <Background />
            <div className="invocation-container">
                <Parchemins onSelectScroll={setSelectedScrollId} selectedScrollId={selectedScrollId} />
                {selectedScrollId && <ListeDispo scrollId={selectedScrollId} />}
            </div>
        </div>
    );
};

export default Invocation;