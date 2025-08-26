import { BrowserRouter, Routes, Route } from 'react-router-dom';
import Form from './pages/Form';
import Accueil from './pages/Accueil';
import Admin from './pages/Admin';

function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<Form />} />
        <Route path="/Accueil" element={<Accueil />} />
        <Route path="/Admin" element={<Admin />} />
      </Routes>
    </BrowserRouter>
  );
}

export default App;