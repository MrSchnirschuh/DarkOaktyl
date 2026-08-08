import React, { useState, useEffect } from 'react';
import { Button } from '@elements/button';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCoins, faExclamationTriangle, faPlus, faHistory } from '@fortawesome/free-solid-svg-icons';
import Loader from '@elements/Loader';

interface BalanceData {
    balance: number;
    reserved_balance: number;
    available_balance: number;
    low_balance_threshold: number;
    is_low_balance: boolean;
}

export default function CreditBalance() {
    const [balance, setBalance] = useState<BalanceData | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [showAddModal, setShowAddModal] = useState(false);
    const [addAmount, setAddAmount] = useState(10);

    useEffect(() => {
        fetchBalance();
    }, []);

    const fetchBalance = async () => {
        try {
            const response = await fetch('/api/client/billing/balance');
            if (response.ok) {
                const data = await response.json();
                setBalance(data.data);
            }
        } catch (err) {
            console.error('Failed to load balance:', err);
        } finally {
            setIsLoading(false);
        }
    };

    const handleAddCredits = async () => {
        try {
            const response = await fetch('/api/client/billing/credits', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ amount: addAmount }),
            });

            if (response.ok) {
                await fetchBalance();
                setShowAddModal(false);
            }
        } catch (err) {
            console.error('Failed to add credits:', err);
        }
    };

    if (isLoading) {
        return <Loader size="small" />;
    }

    if (!balance) {
        return <div>Failed to load balance</div>;
    }

    return (
        <div className="space-y-4">
            <div
                className={`p-6 rounded-lg border ${
                    balance.is_low_balance ? 'bg-orange-50 border-orange-200' : 'bg-green-50 border-green-200'
                }`}
            >
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <div
                            className={`p-3 rounded-full ${balance.is_low_balance ? 'bg-orange-100' : 'bg-green-100'}`}
                        >
                            <FontAwesomeIcon
                                icon={balance.is_low_balance ? faExclamationTriangle : faCoins}
                                className={`text-2xl ${balance.is_low_balance ? 'text-orange-600' : 'text-green-600'}`}
                            />
                        </div>
                        <div>
                            <p className="text-sm text-gray-600">Credit Balance</p>
                            <h3
                                className={`text-3xl font-bold ${
                                    balance.is_low_balance ? 'text-orange-600' : 'text-green-600'
                                }`}
                            >
                                €{balance.balance.toFixed(2)}
                            </h3>
                            {balance.reserved_balance > 0 && (
                                <p className="text-xs text-gray-500">
                                    Reserved: €{balance.reserved_balance.toFixed(2)} | Available: €
                                    {balance.available_balance.toFixed(2)}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="flex gap-2">
                        <Button variant="secondary" onClick={() => setShowAddModal(true)}>
                            <FontAwesomeIcon icon={faPlus} className="mr-2" />
                            Add Credits
                        </Button>
                    </div>
                </div>

                {balance.is_low_balance && (
                    <div className="mt-4 p-3 bg-orange-100 rounded text-orange-800 text-sm flex items-center gap-2">
                        <FontAwesomeIcon icon={faExclamationTriangle} />
                        Your balance is below the threshold of €{balance.low_balance_threshold}
                    </div>
                )}
            </div>

            {showAddModal && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                    <div className="bg-white dark:bg-gray-800 p-6 rounded-lg max-w-md w-full">
                        <h3 className="text-lg font-semibold mb-4">Add Credits</h3>
                        <div className="space-y-4">
                            <label className="block">
                                <span className="text-sm font-medium">Amount (€)</span>
                                <input
                                    type="number"
                                    min="1"
                                    max="1000"
                                    value={addAmount}
                                    onChange={e => setAddAmount(parseInt(e.target.value) || 0)}
                                    className="mt-1 w-full px-3 py-2 border rounded"
                                />
                            </label>
                            <div className="flex gap-3">
                                <Button variant="secondary" onClick={() => setShowAddModal(false)}>
                                    Cancel
                                </Button>
                                <Button variant="primary" onClick={handleAddCredits}>
                                    Add €{addAmount}
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
