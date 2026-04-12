import http from '@/api/http';

export interface AutoScalingConfig {
    enabled: boolean;
    cpu_threshold_up: number;
    cpu_threshold_down: number;
    ram_threshold_up: number;
    ram_threshold_down: number;
    disk_threshold_up: number;
    disk_threshold_down: number;
    scale_up_limit: number;
    scale_down_limit: number;
    scale_up_step: number;
    scale_down_step: number;
    cooldown_minutes: number;
}

export interface AutoScalingResponse {
    data: {
        id: number;
        enabled: boolean;
        thresholds: {
            cpu: { up: number; down: number };
            ram: { up: number; down: number };
            disk: { up: number; down: number };
        };
        limits: {
            scale_up: number;
            scale_down: number;
        };
        steps: {
            up: number;
            down: number;
        };
        cooldown_minutes: number;
        is_in_cooldown: boolean;
        remaining_cooldown_minutes: number;
        last_scale_up_at: string | null;
        last_scale_down_at: string | null;
    };
}

export interface AutoScalingHistoryResponse {
    data: any[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

export const getAutoScalingConfig = async (uuid: string): Promise<AutoScalingResponse['data']> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/auto-scaling`);
    return data.data;
};

export const updateAutoScalingConfig = async (uuid: string, config: Partial<AutoScalingConfig>): Promise<void> => {
    await http.put(`/api/client/servers/${uuid}/auto-scaling`, config);
};

export const getAutoScalingHistory = async (uuid: string): Promise<AutoScalingHistoryResponse> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/auto-scaling/history`);
    return data;
};

export const triggerManualScaling = async (uuid: string): Promise<void> => {
    await http.post(`/api/client/servers/${uuid}/auto-scaling/trigger`);
};
