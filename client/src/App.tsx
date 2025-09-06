import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { UserProvider, useUser } from './context/UserContext';
import Form from './pages/Form';
import Accueil from './pages/Accueil';
import Admin from './pages/Admin';
import Invocation from './pages/Invocation';
import './App.css';

// Composant de routes protégées qui utilise le contexte
const AppRoutes = () => {
  const { isLoggedIn, isAdmin, loading } = useUser();

  // Afficher un indicateur de chargement pendant la vérification
  if (loading) {
    return <div className="loading-screen">Chargement...</div>;
  }

  return (
    <Routes>
      {/* Route de la page d'accueil/connexion avec redirection si déjà connecté */}
      <Route
        path="/"
        element={isLoggedIn ? <Navigate to="/Accueil" replace /> : <Form />}
      />

      {/* Routes protégées - rediriger vers la connexion si non connecté */}
      <Route
        path="/Accueil"
        element={isLoggedIn ? <Accueil /> : <Navigate to="/" replace />}
      />
      <Route
        path="/Invocation"
        element={isLoggedIn ? <Invocation /> : <Navigate to="/" replace />}
      />

      {/* Route Admin - vérifier si l'utilisateur est admin */}
      <Route
        path="/Admin"
        element={isLoggedIn && isAdmin ? <Admin /> : <Navigate to={isLoggedIn ? "/Accueil" : "/"} replace />}
      />
    </Routes>
  );
};

function App() {
  return (
    <UserProvider>
      <BrowserRouter>
        <AppRoutes />
      </BrowserRouter>
    </UserProvider>
  );
}

export default App;