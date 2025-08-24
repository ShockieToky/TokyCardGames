import { BrowserRouter, Routes, Route } from 'react-router-dom';
import Form from './pages/Form';
import Testconnexion from './pages/Testconnexion';

function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<Form />} />
        <Route path="/Testconnexion" element={<Testconnexion />} />
      </Routes>
    </BrowserRouter>
  );
}

export default App;