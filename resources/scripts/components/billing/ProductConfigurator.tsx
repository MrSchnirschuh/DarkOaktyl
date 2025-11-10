import { useState, useEffect } from 'react';
import { Button } from '@elements/button';
import ContentBox from '@elements/ContentBox';
import Field from '@elements/Field';
import Spinner from '@elements/Spinner';
import { calculatePrice, PriceCalculationRequest } from '@/api/billing/calculatePrice';
import { Category } from '@/api/billing/getCategories';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faShoppingCart, faMicrochip, faMemory, faHdd, faDatabase, faArchive, faEthernet } from '@fortawesome/free-solid-svg-icons';

interface Props {
    category: Category;
    onProceed?: (configuration: PriceCalculationRequest, price: number) => void;
}

export default ({ category, onProceed }: Props) => {
    const [cpu, setCpu] = useState(100);
    const [memory, setMemory] = useState(1024);
    const [disk, setDisk] = useState(4096);
    const [backups, setBackups] = useState(0);
    const [databases, setDatabases] = useState(0);
    const [allocations, setAllocations] = useState(1);
    const [durationDays, setDurationDays] = useState(30);
    const [price, setPrice] = useState<number | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    // Calculate price whenever any resource value changes
    useEffect(() => {
        if (!category.pricingConfigurationId) return;

        const timeoutId = setTimeout(() => {
            setLoading(true);
            setError(null);

            const request: PriceCalculationRequest = {
                pricing_configuration_id: category.pricingConfigurationId,
                cpu,
                memory,
                disk,
                backups,
                databases,
                allocations,
                duration_days: durationDays,
            };

            calculatePrice(request)
                .then(response => {
                    setPrice(response.final_price);
                    setLoading(false);
                })
                .catch(err => {
                    console.error('Error calculating price:', err);
                    setError('Failed to calculate price. Please try again.');
                    setLoading(false);
                });
        }, 300); // Debounce for 300ms

        return () => clearTimeout(timeoutId);
    }, [cpu, memory, disk, backups, databases, allocations, durationDays, category.pricingConfigurationId]);

    const handleProceed = () => {
        if (price !== null && category.pricingConfigurationId && onProceed) {
            const configuration: PriceCalculationRequest = {
                pricing_configuration_id: category.pricingConfigurationId,
                cpu,
                memory,
                disk,
                backups,
                databases,
                allocations,
                duration_days: durationDays,
            };
            onProceed(configuration, price);
        }
    };

    if (!category.useConfigurator || !category.pricingConfigurationId) {
        return (
            <ContentBox title="Configuration Not Available">
                <p className="text-sm text-gray-400">
                    This category does not support custom configuration. Please contact support.
                </p>
            </ContentBox>
        );
    }

    return (
        <ContentBox title="Configure Your Server">
            <div className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-300 mb-2">
                            <FontAwesomeIcon icon={faMicrochip} className="mr-2" />
                            CPU Limit (%)
                        </label>
                        <input
                            type="number"
                            min="10"
                            max="800"
                            step="10"
                            value={cpu}
                            onChange={e => setCpu(Number(e.target.value))}
                            className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-gray-100"
                        />
                        <p className="text-xs text-gray-400 mt-1">Percentage of a CPU thread</p>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-300 mb-2">
                            <FontAwesomeIcon icon={faMemory} className="mr-2" />
                            Memory (MB)
                        </label>
                        <input
                            type="number"
                            min="128"
                            max="32768"
                            step="128"
                            value={memory}
                            onChange={e => setMemory(Number(e.target.value))}
                            className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-gray-100"
                        />
                        <p className="text-xs text-gray-400 mt-1">Server RAM allocation</p>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-300 mb-2">
                            <FontAwesomeIcon icon={faHdd} className="mr-2" />
                            Disk Space (MB)
                        </label>
                        <input
                            type="number"
                            min="512"
                            max="102400"
                            step="512"
                            value={disk}
                            onChange={e => setDisk(Number(e.target.value))}
                            className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-gray-100"
                        />
                        <p className="text-xs text-gray-400 mt-1">Storage space allocation</p>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-300 mb-2">
                            <FontAwesomeIcon icon={faDatabase} className="mr-2" />
                            Databases
                        </label>
                        <input
                            type="number"
                            min="0"
                            max="10"
                            value={databases}
                            onChange={e => setDatabases(Number(e.target.value))}
                            className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-gray-100"
                        />
                        <p className="text-xs text-gray-400 mt-1">Number of databases</p>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-300 mb-2">
                            <FontAwesomeIcon icon={faArchive} className="mr-2" />
                            Backups
                        </label>
                        <input
                            type="number"
                            min="0"
                            max="10"
                            value={backups}
                            onChange={e => setBackups(Number(e.target.value))}
                            className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-gray-100"
                        />
                        <p className="text-xs text-gray-400 mt-1">Number of backup slots</p>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-300 mb-2">
                            <FontAwesomeIcon icon={faEthernet} className="mr-2" />
                            Allocations
                        </label>
                        <input
                            type="number"
                            min="1"
                            max="5"
                            value={allocations}
                            onChange={e => setAllocations(Number(e.target.value))}
                            className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-gray-100"
                        />
                        <p className="text-xs text-gray-400 mt-1">Number of port allocations</p>
                    </div>
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                        Billing Period
                    </label>
                    <select
                        value={durationDays}
                        onChange={e => setDurationDays(Number(e.target.value))}
                        className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-gray-100"
                    >
                        <option value={30}>Monthly (30 days)</option>
                        <option value={90}>Quarterly (90 days)</option>
                        <option value={180}>Semi-Annually (180 days)</option>
                        <option value={365}>Annually (365 days)</option>
                    </select>
                </div>

                {error && (
                    <div className="bg-red-500/10 border border-red-500 rounded p-4">
                        <p className="text-sm text-red-400">{error}</p>
                    </div>
                )}

                <div className="flex items-center justify-between p-4 bg-gray-700 rounded">
                    <div>
                        <p className="text-sm text-gray-400">Estimated Price</p>
                        <p className="text-2xl font-bold text-white">
                            {loading ? (
                                <Spinner size="small" />
                            ) : price !== null ? (
                                `$${price.toFixed(2)} / ${durationDays} days`
                            ) : (
                                'Calculating...'
                            )}
                        </p>
                    </div>
                    <Button
                        onClick={handleProceed}
                        disabled={loading || price === null || error !== null}
                    >
                        <FontAwesomeIcon icon={faShoppingCart} className="mr-2" />
                        Proceed to Checkout
                    </Button>
                </div>
            </div>
        </ContentBox>
    );
};
