import { useEffect, useRef } from 'react';
import { useStoreActions, useStoreState } from '@/state/hooks';
import { resolveThemeMode } from '@/state/theme';

const AppearanceSync = () => {
    const user = useStoreState(state => state.user.data);
    const preference = useStoreState(state => state.theme.preference ?? 'system');
    const mode = useStoreState(state => state.theme.mode ?? 'dark');

    const setMode = useStoreActions(actions => actions.theme.setMode);
    const setPreference = useStoreActions(actions => actions.theme.setPreference);
    const updateAppearance = useStoreActions(actions => actions.user.updateAppearance);

    const lastPersistedRef = useRef<{ preference: 'light' | 'dark' | 'system'; mode: 'light' | 'dark' } | null>(null);
    const hasInitialSyncRef = useRef(false);
    const lastUserPreferenceRef = useRef<'light' | 'dark' | 'system' | null>(null);

    // Ensure theme store preference mirrors the loaded user data once available.
    useEffect(() => {
        if (!user) return;
        const target = user.appearanceMode;

        if (!hasInitialSyncRef.current) {
            hasInitialSyncRef.current = true;
            lastUserPreferenceRef.current = target;

            if (target !== preference) {
                setPreference(target);
            }

            return;
        }

        if (lastUserPreferenceRef.current !== target) {
            lastUserPreferenceRef.current = target;

            if (target !== preference) {
                setPreference(target);
            }
        }
    }, [user?.appearanceMode, preference, setPreference]);

    // Whenever preference changes, compute the effective mode, respecting system settings.
    useEffect(() => {
        if (!user) return;
        const resolved = resolveThemeMode(preference, user.appearanceLastMode ?? 'dark');
        if (resolved !== mode) {
            setMode(resolved);
        }
    }, [user?.appearanceLastMode, preference, mode, setMode, user]);

    // Keep the resolved mode in sync with system preference when configured to follow the system theme.
    useEffect(() => {
        if (preference !== 'system') return;
        if (typeof window === 'undefined' || !window.matchMedia) return;

        const matcher = window.matchMedia('(prefers-color-scheme: dark)');
        const handler = () => {
            setMode(matcher.matches ? 'dark' : 'light');
        };

        handler();

        if (typeof matcher.addEventListener === 'function') {
            matcher.addEventListener('change', handler);
            return () => matcher.removeEventListener('change', handler);
        }

        matcher.addListener(handler);
        return () => matcher.removeListener(handler);
    }, [preference, setMode]);

    // Persist preference & resolved mode when the in-app state diverges from the backend values.
    useEffect(() => {
        if (!user) return;

        const matchesBackend = user.appearanceMode === preference && user.appearanceLastMode === mode;
        if (matchesBackend) {
            lastPersistedRef.current = { preference, mode };
            return;
        }

        const lastAttempt = lastPersistedRef.current;
        if (lastAttempt && lastAttempt.preference === preference && lastAttempt.mode === mode) {
            return;
        }

        lastPersistedRef.current = { preference, mode };

        updateAppearance({ mode: preference, lastMode: mode }).catch(() => {
            lastPersistedRef.current = null;
        });
    }, [user, preference, mode, updateAppearance]);

    return null;
};

export default AppearanceSync;
