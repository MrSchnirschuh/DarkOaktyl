import http from '@/api/http';

export interface UserAlert {
  id: number;
  uuid: string;
  title: string;
  message: string;
  type: 'info' | 'warning' | 'error' | 'security';
  target: 'global' | 'admin' | 'user';
  is_persistent: boolean;
  is_active: boolean;
  color_class: string;
  is_security: boolean;
  created_at: string;
}

export interface UserAlertListResponse {
  object: 'list';
  data: UserAlert[];
}

export const getUserAlerts = async (): Promise<UserAlertListResponse> => {
  return new Promise((resolve, reject) => {
    http.get('/api/client/account/alerts')
      .then(({ data }) => resolve(data))
      .catch(reject);
  });
};

export const dismissAlert = async (uuid: string): Promise<{ message: string }> => {
  return new Promise((resolve, reject) => {
    http.post(`/api/client/account/alerts/${uuid}/dismiss`)
      .then(({ data }) => resolve(data))
      .catch(reject);
  });
};
