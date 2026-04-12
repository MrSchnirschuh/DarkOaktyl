import { useState, useEffect } from 'react';
import { Alert, getUserAlerts, dismissAlert } from '@/api/admin/alerts-new';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faInfoCircle, faExclamationTriangle, faExclamationCircle, faShieldAlt, faTimes } from '@fortawesome/free-solid-svg-icons';
import classNames from 'classnames';
import useFlash from '@/plugins/useFlash';

const typeIcons = {
  info: faInfoCircle,
  warning: faExclamationTriangle,
  error: faExclamationCircle,
  security: faShieldAlt,
};

const typeStyles = {
  info: {
    border: 'border-blue-500',
    bg: 'bg-blue-500/10',
    icon: 'text-blue-500',
  },
  warning: {
    border: 'border-yellow-500',
    bg: 'bg-yellow-500/10',
    icon: 'text-yellow-500',
  },
  error: {
    border: 'border-red-500',
    bg: 'bg-red-500/10',
    icon: 'text-red-500',
  },
  security: {
    border: 'border-red-600',
    bg: 'bg-red-600/10',
    icon: 'text-red-600',
  },
};

export default () => {
  const [alerts, setAlerts] = useState<Alert[]>([]);
  const [loading, setLoading] = useState(true);
  const { clearAndAddHttpError } = useFlash();

  const fetchAlerts = async () => {
    try {
      const response = await getUserAlerts();
      setAlerts(response.data);
    } catch (error) {
      // Silent fail - don't show error to user
      console.error('Failed to fetch alerts:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchAlerts();
  }, []);

  const handleDismiss = async (uuid: string) => {
    try {
      await dismissAlert(uuid);
      // Remove from local state
      setAlerts((prev) => prev.filter((a) => a.uuid !== uuid));
    } catch (error) {
      clearAndAddHttpError({ key: 'user-alerts', error });
    }
  };

  if (loading || alerts.length === 0) {
    return null;
  }

  return (
    <div className="space-y-3 mb-6">
      {alerts.map((alert) => {
        const styles = typeStyles[alert.type];
        const icon = typeIcons[alert.type];

        return (
          <div
            key={alert.uuid}
            className={classNames(
              'relative rounded-lg border-l-4 p-4 pr-12 shadow-sm',
              styles.border,
              styles.bg
            )}
          >
            {/* Icon */}
            <div className="flex items-start">
              <FontAwesomeIcon
                icon={icon}
                className={classNames('mt-1 mr-3 text-xl flex-shrink-0', styles.icon)}
              />
              <div className="flex-1 min-w-0">
                <h4 className="font-medium text-theme-primary">{alert.title}</h4>
                <p className="text-theme-secondary text-sm mt-1 whitespace-pre-wrap">
                  {alert.message}
                </p>
              </div>
            </div>

            {/* Dismiss button (only for non-persistent alerts) */}
            {!alert.is_persistent && (
              <button
                onClick={() => handleDismiss(alert.uuid)}
                className="absolute top-3 right-3 text-theme-muted hover:text-theme-primary transition-colors"
                title="Dismiss alert"
              >
                <FontAwesomeIcon icon={faTimes} className="w-4 h-4" />
              </button>
            )}
          </div>
        );
      })}
    </div>
  );
};
