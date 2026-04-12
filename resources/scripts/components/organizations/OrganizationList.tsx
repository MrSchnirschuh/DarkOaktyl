import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { getOrganizations, Organization, createOrganization } from '@/api/organizations';
import { Button } from '@/components/elements/button';
import { Input } from '@/components/elements/Input';
import { Dialog } from '@/components/elements/dialog';
import { Card } from '@/components/elements/Card';
import { Spinner } from '@/components/elements/Spinner';
import { useToast } from '@/hooks/useToast';
import { Plus, Users, Server } from 'lucide-react';

const OrganizationList: React.FC = () => {
    const [organizations, setOrganizations] = useState<Organization[]>([]);
    const [loading, setLoading] = useState(true);
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [newOrgName, setNewOrgName] = useState('');
    const [newOrgDescription, setNewOrgDescription] = useState('');
    const [creating, setCreating] = useState(false);
    const { addToast } = useToast();

    const fetchOrganizations = async () => {
        try {
            const response = await getOrganizations();
            setOrganizations(response.data.data || []);
        } catch (error) {
            addToast({ type: 'error', message: 'Failed to load organizations' });
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchOrganizations();
    }, []);

    const handleCreate = async () => {
        if (!newOrgName.trim()) return;
        
        setCreating(true);
        try {
            await createOrganization({
                name: newOrgName.trim(),
                description: newOrgDescription.trim() || undefined,
            });
            addToast({ type: 'success', message: 'Organization created successfully' });
            setCreateModalOpen(false);
            setNewOrgName('');
            setNewOrgDescription('');
            fetchOrganizations();
        } catch (error: any) {
            addToast({ 
                type: 'error', 
                message: error.response?.data?.error || 'Failed to create organization' 
            });
        } finally {
            setCreating(false);
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center h-64">
                <Spinner size="large" />
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h1 className="text-3xl font-bold text-gray-100">Organizations</h1>
                <Button onClick={() => setCreateModalOpen(true)}>
                    <Plus className="w-4 h-4 mr-2" />
                    Create Organization
                </Button>
            </div>

            {organizations.length === 0 ? (
                <Card className="p-12 text-center">
                    <Users className="w-16 h-16 mx-auto text-gray-500 mb-4" />
                    <h3 className="text-xl font-semibold text-gray-300 mb-2">
                        No Organizations Yet
                    </h3>
                    <p className="text-gray-500 mb-6">
                        Create an organization to collaborate with others and share server costs.
                    </p>
                    <Button onClick={() => setCreateModalOpen(true)}>
                        Create Your First Organization
                    </Button>
                </Card>
            ) : (
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {organizations.map((org) => (
                        <Link
                            key={org.id}
                            to={`/organizations/${org.slug}`}
                            className="block"
                        >
                            <Card className="p-6 hover:bg-gray-800/50 transition-colors">
                                <div className="flex items-start justify-between mb-4">
                                    <div>
                                        <h3 className="text-xl font-semibold text-gray-100 mb-1">
                                            {org.name}
                                        </h3>
                                        {org.description && (
                                            <p className="text-sm text-gray-400 line-clamp-2">
                                                {org.description}
                                            </p>
                                        )}
                                    </div>
                                    {org.avatar ? (
                                        <img
                                            src={org.avatar}
                                            alt={org.name}
                                            className="w-12 h-12 rounded-lg"
                                        />
                                    ) : (
                                        <div className="w-12 h-12 rounded-lg bg-gray-700 flex items-center justify-center">
                                            <Users className="w-6 h-6 text-gray-400" />
                                        </div>
                                    )}
                                </div>
                                <div className="flex items-center gap-4 text-sm text-gray-500">
                                    <div className="flex items-center">
                                        <Users className="w-4 h-4 mr-1" />
                                        {org.members_count} members
                                    </div>
                                    <div className="flex items-center">
                                        <Server className="w-4 h-4 mr-1" />
                                        {org.servers_count} servers
                                    </div>
                                </div>
                            </Card>
                        </Link>
                    ))}
                </div>
            )}

            <Dialog
                open={createModalOpen}
                onClose={() => setCreateModalOpen(false)}
                title="Create Organization"
            >
                <div className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-300 mb-1">
                            Organization Name
                        </label>
                        <Input
                            value={newOrgName}
                            onChange={(e) => setNewOrgName(e.target.value)}
                            placeholder="My Team"
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-300 mb-1">
                            Description (Optional)
                        </label>
                        <Input
                            value={newOrgDescription}
                            onChange={(e) => setNewOrgDescription(e.target.value)}
                            placeholder="A brief description of your organization"
                        />
                    </div>
                    <div className="flex justify-end gap-3 pt-4">
                        <Button
                            variant="secondary"
                            onClick={() => setCreateModalOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={handleCreate}
                            disabled={!newOrgName.trim() || creating}
                        >
                            {creating ? <Spinner size="small" /> : 'Create'}
                        </Button>
                    </div>
                </div>
            </Dialog>
        </div>
    );
};

export default OrganizationList;
