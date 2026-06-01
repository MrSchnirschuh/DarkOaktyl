import http from '@/api/http';
import { PanelMode } from '@/state/settings';

export interface GeneralSettings {
    name: string;
    auto_update: boolean;
    indicators: boolean;
    speed_dial: boolean;
    // Presets module toggle and global port ranges used as fallback when saving presets
    presets_module?: boolean;
    presets_global_port_start?: number | null;
    presets_global_port_end?: number | null;
}

export const updateGeneralSettings = async (settings: Partial<GeneralSettings>): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.patch(`/api/application/settings`, settings)
            .then(() => resolve())
            .catch(reject);
    });
};

export const updateModeSettings = async (mode: PanelMode): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.patch(`/api/application/settings/mode`, mode)
            .then(() => resolve())
            .catch(reject);
    });
};
