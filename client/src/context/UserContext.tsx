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

const UserContext = createContext<UserContextType>({
    isLoggedIn: false,
    isAdmin: false,
    loading: true,
    user: null,
    login: async () => ({ success: false }),
    logout: async () => { },
    checkSession: async () => { },
});

export const useUser = () => useContext(UserContext);

export const UserProvider: React.FC<UserProviderProps> = ({ children }) => {
    const [isLoggedIn, setIsLoggedIn] = useState(false);
    const [isAdmin, setIsAdmin] = useState(false);
    const [loading, setLoading] = useState(true);
    const [user, setUser] = useState<UserData | null>(null);

    const resetUser = () => {
        setIsLoggedIn(false);
        setIsAdmin(false);
        setUser(null);
    };

    const checkSession = async () => {
        setLoading(true);
        try {
            const response = await fetch(`${API_URL}/user/me`, {
                method: 'GET',
                credentials: 'include'
            });
            const data = response.ok ? await response.json() : null;

            if (data?.success) {
                setIsLoggedIn(true);
                setIsAdmin(!!data.isAdmin);
                setUser({
                    userId: data.userId,
                    pseudo: data.pseudo,
                    isAdmin: !!data.isAdmin
                });
            } else {
                resetUser();
            }
        } catch (error) {
            console.error('Erreur lors de la vérification de session:', error);
            resetUser();
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        checkSession();
    }, []);

    const login = async (pseudo: string, password: string) => {
        try {
            const response = await fetch(`${API_URL}/user/login`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ pseudo, password }),
            });
            const data = await response.json();

            if (data.success) {
                setIsLoggedIn(true);
                setIsAdmin(!!data.isAdmin);
                setUser({
                    userId: data.userId,
                    pseudo,
                    isAdmin: !!data.isAdmin
                });
                return { success: true };
            }
            resetUser();
            return { success: false, error: data.error || 'Identifiants invalides' };
        } catch (error) {
            console.error('Erreur lors de la connexion:', error);
            resetUser();
            return { success: false, error: 'Erreur de connexion au serveur' };
        }
    };

    const logout = async () => {
        try {
            const response = await fetch(`${API_URL}/logout`, {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' }
            });
            if (response.ok) {
                resetUser();
            } else {
                console.error('Erreur lors de la déconnexion:', await response.text());
            }
        } catch (error) {
            console.error('Erreur réseau lors de la déconnexion:', error);
        }
    };

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