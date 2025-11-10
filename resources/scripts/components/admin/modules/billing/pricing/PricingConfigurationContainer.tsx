import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { getPricingConfiguration } from '@/api/admin/billing/pricing';
import { PricingConfiguration } from '@/api/definitions/admin';
import Spinner from '@elements/Spinner';
import PricingConfigurationForm from './PricingConfigurationForm';

export default () => {
    const params = useParams<'id'>();
    const [configuration, setConfiguration] = useState<PricingConfiguration>();
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (params.id) {
            getPricingConfiguration(Number(params.id))
                .then(config => {
                    setConfiguration(config);
                    setLoading(false);
                })
                .catch(error => {
                    console.error(error);
                    setLoading(false);
                });
        } else {
            setLoading(false);
        }
    }, [params.id]);

    if (loading) {
        return <Spinner size={'large'} centered />;
    }

    return <PricingConfigurationForm configuration={configuration} />;
};
