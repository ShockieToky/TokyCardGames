import { BrowserRouter, Routes, Route } from 'react-router-dom';
import Form from './pages/Form';
import Accueil from './pages/Accueil';

function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<Form />} />
        <Route path="/Accueil" element={<Accueil />} />
      </Routes>
    </BrowserRouter>
  );
}

export default App;