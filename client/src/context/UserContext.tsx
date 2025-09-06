import React, { createContext, useState, useContext, useEffect, ReactNode } from 'react';

const API_URL = 'http://localhost:8000';

interface UserContextType {
    isLoggedIn: boolean;
    isAdmin: boolean;
    loading: boolean;
    user: UserData | null;
    login: (pseudo: string, password: string) => Promise<{ success: boolean, error?: string }>;
    logout: () => Promise<void>;
    checkSession: () => Promise<void>;
}

interface UserData {
    userId: number;
    pseudo: string;
    isAdmin: boolean;
}

interface UserProviderProps {
    children: ReactNode;
}

// Création du contexte avec des valeurs par défaut
const UserContext = createContext<UserContextType>({
    isLoggedIn: false,
    isAdmin: false,
    loading: true,
    user: null,
    login: async () => ({ success: false }),
    logout: async () => { },
    checkSession: async () => { },
});

// Hook personnalisé pour utiliser le contexte
export const useUser = () => useContext(UserContext);

// Provider qui va englober l'application
export const UserProvider: React.FC<UserProviderProps> = ({ children }) => {
    const [isLoggedIn, setIsLoggedIn] = useState<boolean>(false);
    const [isAdmin, setIsAdmin] = useState<boolean>(false);
    const [loading, setLoading] = useState<boolean>(true);
    const [user, setUser] = useState<UserData | null>(null);

    // Fonction pour vérifier la session utilisateur
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

                if (data.success) {
                    setIsLoggedIn(true);
                    setIsAdmin(data.isAdmin || false);
                    setUser({
                        userId: data.userId,
                        pseudo: data.pseudo,
                        isAdmin: data.isAdmin || false
                    });
                } else {
                    setIsLoggedIn(false);
                    setUser(null);
                }
            } else {
                console.log("Échec de vérification de session");
                setIsLoggedIn(false);
                setUser(null);
            }
        } catch (error) {
            console.error('Erreur lors de la vérification de session:', error);
            setIsLoggedIn(false);
            setUser(null);
        } finally {
            setLoading(false);
        }
    };

    // Vérification de session au chargement
    useEffect(() => {
        checkSession();
    }, []);

    // Fonction de connexion
    const login = async (pseudo: string, password: string): Promise<{ success: boolean, error?: string }> => {
        try {
            console.log("Tentative de connexion pour:", pseudo);

            const response = await fetch(`${API_URL}/user/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({ pseudo, password }),
            });

            console.log("Statut de réponse de connexion:", response.status);

            const data = await response.json();
            console.log("Données de connexion:", data);

            if (data.success) {
                // Mise à jour du contexte avec les informations utilisateur
                setIsLoggedIn(true);
                setIsAdmin(data.isAdmin || false);

                setUser({
                    userId: data.userId,
                    pseudo: pseudo,
                    isAdmin: data.isAdmin || false
                });

                return { success: true };
            } else {
                return { success: false, error: data.error || 'Identifiants invalides' };
            }
        } catch (error) {
            console.error('Erreur lors de la connexion:', error);
            return { success: false, error: 'Erreur de connexion au serveur' };
        }
    };

    // Fonction de déconnexion
    const logout = async (): Promise<void> => {
        try {
            const response = await fetch(`${API_URL}/logout`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (response.ok) {
                // Réinitialiser le contexte
                setIsLoggedIn(false);
                setIsAdmin(false);
                setUser(null);
            } else {
                console.error('Erreur lors de la déconnexion:', await response.text());
            }
        } catch (error) {
            console.error('Erreur réseau lors de la déconnexion:', error);
        }
    };

    // Valeurs fournies par le contexte
    const value = {
        isLoggedIn,
        isAdmin,
        loading,
        user,
        login,
        logout,
        checkSession
    };

    return (
        <UserContext.Provider value={value}>
            {children}
        </UserContext.Provider>
    );
};