import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { useState, useEffect } from 'react';
import Form from './pages/Form';
import Accueil from './pages/Accueil';
import Admin from './pages/Admin';
import Invocation from './pages/Invocation';

const API_URL = 'http://localhost:8000';

function App() {
  const [isLoggedIn, setIsLoggedIn] = useState<boolean | null>(null);
  const [isAdmin, setIsAdmin] = useState<boolean>(false);
  const [loading, setLoading] = useState<boolean>(true);

  // Fonction pour gérer la connexion et le rafraîchissement de la page
  const handleLogin = async (pseudo: string, password: string): Promise<{ success: boolean, error?: string }> => {
    try {
      const response = await fetch(`${API_URL}/user/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({ pseudo, password }),
      });

      const data = await response.json();

      if (data.success) {
        // Si la connexion réussit, on met à jour l'état local puis on rafraîchit la page
        setIsLoggedIn(true);
        setIsAdmin(data.isAdmin || false);

        // Rafraîchir la page pour forcer la vérification de session
        window.location.reload();
        return { success: true };
      } else {
        return { success: false, error: data.error || 'Erreur de connexion' };
      }
    } catch (error) {
      console.error('Erreur lors de la connexion:', error);
      return { success: false, error: 'Erreur de connexion au serveur' };
    }
  };

  // Vérifier la session au chargement de l'application
  useEffect(() => {
    const checkSession = async () => {
      console.log("Vérification de session...");
      try {
        const response = await fetch(`${API_URL}/user/me`, {
          method: 'GET',
          credentials: 'include'
        });

        console.log("Réponse status:", response.status);

        if (response.ok) {
          const data = await response.json();
          console.log("Données de session:", data);
          setIsLoggedIn(data.success);
          setIsAdmin(data.isAdmin || false);
        } else {
          console.log("Échec de vérification");
          setIsLoggedIn(false);
        }
      } catch (error) {
        console.error('Erreur lors de la vérification de session:', error);
        setIsLoggedIn(false);
      } finally {
        setLoading(false);
      }
    };

    checkSession();
  }, []);

  // Afficher un indicateur de chargement pendant la vérification
  if (loading) {
    return <div className="loading-screen">Chargement...</div>;
  }

  return (
    <BrowserRouter>
      <Routes>
        {/* Route de la page d'accueil/connexion avec redirection si déjà connecté */}
        <Route
          path="/"
          element={isLoggedIn ? <Navigate to="/Accueil" replace /> : <Form onLogin={handleLogin} />}
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
    </BrowserRouter>
  );
}

export default App;