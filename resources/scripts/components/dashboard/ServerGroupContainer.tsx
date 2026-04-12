import React, { useState, useEffect } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faFolder,
    faPlus,
    faCog,
    faTrash,
    faEdit,
    faServer,
    faPalette,
    faTimes,
} from '@fortawesome/free-solid-svg-icons';
import { Button } from '@/elements/button';
import { Dialog } from '@/elements/dialog';
import { Field } from '@/elements/field';
import { getServerGroups, createServerGroup, updateServerGroup, deleteServerGroup, addServerToGroup, removeServerFromGroup } from '@/api/routes/server/groups';
import { getServers } from '@/api/getServers';
import { ServerGroup, Server } from '@definitions/server';
import ServerRow from './ServerRow';
import useFlash from '@/plugins/useFlash';
import FlashMessageRender from '@/elements/FlashMessageRender';
import { VisibleDialog } from '@account/groups/ServerGroupDialog';

interface GroupWithServers extends ServerGroup {
    servers: Server[];
}

const PRESET_COLORS = [
    '#EF4444', // red
    '#F97316', // orange
    '#EAB308', // yellow
    '#22C55E', // green
    '#3B82F6', // blue
    '#8B5CF6', // purple
    '#EC4899', // pink
    '#6B7280', // gray
];

export default function ServerGroupContainer() {
    const [groups, setGroups] = useState<GroupWithServers[]>([]);
    const [ungroupedServers, setUngroupedServers] = useState<Server[]>([]);
    const [loading, setLoading] = useState(true);
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingGroup, setEditingGroup] = useState<GroupWithServers | null>(null);
    const [groupName, setGroupName] = useState('');
    const [groupDescription, setGroupDescription] = useState('');
    const [groupColor, setGroupColor] = useState('#3B82F6');
    const [groupIcon, setGroupIcon] = useState('folder');
    const [serverGroupsDialog, setServerGroupsDialog] = useState<VisibleDialog>({ open: 'none' });
    const { clearAndAddHttpError, clearFlashes, addFlash } = useFlash();

    useEffect(() => {
        loadData();
    }, []);

    const loadData = async () => {
        setLoading(true);
        clearFlashes();

        try {
            const [groupsData, serversData] = await Promise.all([
                getServerGroups(),
                getServers(),
            ]);

            const servers = Array.isArray(serversData) ? serversData : (serversData as any).data || [];

            // Map servers to groups
            const groupsWithServers: GroupWithServers[] = groupsData.map(group => ({
                ...group,
                servers: servers.filter((s: Server) => s.groupId === group.id),
            }));

            setGroups(groupsWithServers);
            setUngroupedServers(servers.filter((s: Server) => !s.groupId));
        } catch (error) {
            clearAndAddHttpError({ key: 'dashboard:groups', error });
        } finally {
            setLoading(false);
        }
    };

    const openCreateDialog = () => {
        setEditingGroup(null);
        setGroupName('');
        setGroupDescription('');
        setGroupColor('#3B82F6');
        setGroupIcon('folder');
        setDialogOpen(true);
    };

    const openEditDialog = (group: GroupWithServers) => {
        setEditingGroup(group);
        setGroupName(group.name);
        setGroupDescription((group as any).description || '');
        setGroupColor(group.color || '#3B82F6');
        setGroupIcon((group as any).icon || 'folder');
        setDialogOpen(true);
    };

    const handleSave = async () => {
        if (!groupName.trim()) return;

        try {
            if (editingGroup) {
                await updateServerGroup(editingGroup.id, {
                    name: groupName,
                    description: groupDescription,
                    color: groupColor,
                    icon: groupIcon,
                });
                addFlash({ type: 'success', key: 'dashboard:groups', message: 'Group updated successfully.' });
            } else {
                await createServerGroup({
                    name: groupName,
                    description: groupDescription,
                    color: groupColor,
                    icon: groupIcon,
                });
                addFlash({ type: 'success', key: 'dashboard:groups', message: 'Group created successfully.' });
            }
            setDialogOpen(false);
            await loadData();
        } catch (error) {
            clearAndAddHttpError({ key: 'dashboard:groups', error });
        }
    };

    const handleDelete = async (groupId: number) => {
        if (!confirm('Are you sure you want to delete this group? Servers will remain but be ungrouped.')) return;

        try {
            await deleteServerGroup(groupId);
            addFlash({ type: 'success', key: 'dashboard:groups', message: 'Group deleted successfully.' });
            await loadData();
        } catch (error) {
            clearAndAddHttpError({ key: 'dashboard:groups', error });
        }
    };

    const handleAddServerToGroup = async (serverUuid: string, groupId: number) => {
        try {
            await addServerToGroup(groupId, serverUuid);
            addFlash({ type: 'success', key: 'dashboard:groups', message: 'Server added to group.' });
            await loadData();
        } catch (error) {
            clearAndAddHttpError({ key: 'dashboard:groups', error });
        }
    };

    const handleRemoveServerFromGroup = async (serverUuid: string, groupId: number) => {
        try {
            await removeServerFromGroup(groupId, serverUuid);
            addFlash({ type: 'success', key: 'dashboard:groups', message: 'Server removed from group.' });
            await loadData();
        } catch (error) {
            clearAndAddHttpError({ key: 'dashboard:groups', error });
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center h-64">
                <div className="text-theme-secondary">Loading server groups...</div>
            </div>
        );
    }

    return (
        <div className="w-full">
            <FlashMessageRender byKey="dashboard:groups" />

            {/* Header */}
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-theme-primary flex items-center gap-2">
                    <FontAwesomeIcon icon={faFolder} className="text-theme-icon" />
                    Server Groups
                </h2>
                <Button onClick={openCreateDialog}>
                    <FontAwesomeIcon icon={faPlus} className="mr-2" />
                    Create Group
                </Button>
            </div>

            {/* Groups Grid */}
            <div className="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4 mb-8">
                {groups.map(group => (
                    <div
                        key={group.id}
                        className="bg-theme-secondary/10 rounded-lg p-4 border border-theme-border"
                        style={{ borderLeft: `4px solid ${group.color || '#3B82F6'}` }}
                    >
                        {/* Group Header */}
                        <div className="flex items-center justify-between mb-3">
                            <div className="flex items-center gap-2">
                                <FontAwesomeIcon
                                    icon={faFolder}
                                    style={{ color: group.color || '#3B82F6' }}
                                />
                                <span className="font-semibold text-theme-primary">{group.name}</span>
                                <span className="text-xs text-theme-secondary bg-theme-secondary/20 px-2 py-0.5 rounded-full">
                                    {group.servers.length} server{group.servers.length !== 1 ? 's' : ''}
                                </span>
                            </div>
                            <div className="flex gap-1">
                                <button
                                    onClick={() => openEditDialog(group)}
                                    className="p-1.5 text-theme-secondary hover:text-theme-primary transition-colors"
                                    title="Edit group"
                                >
                                    <FontAwesomeIcon icon={faEdit} size="sm" />
                                </button>
                                <button
                                    onClick={() => handleDelete(group.id)}
                                    className="p-1.5 text-theme-secondary hover:text-red-500 transition-colors"
                                    title="Delete group"
                                >
                                    <FontAwesomeIcon icon={faTrash} size="sm" />
                                </button>
                            </div>
                        </div>

                        {/* Group Description */}
                        {(group as any).description && (
                            <p className="text-sm text-theme-secondary mb-3">{(group as any).description}</p>
                        )}

                        {/* Servers in Group */}
                        <div className="space-y-2">
                            {group.servers.length === 0 ? (
                                <div className="text-center py-4 text-theme-muted text-sm">
                                    No servers in this group
                                </div>
                            ) : (
                                group.servers.map(server => (
                                    <div
                                        key={server.uuid}
                                        className="flex items-center justify-between bg-theme-background/50 rounded p-2"
                                    >
                                        <div className="flex items-center gap-2">
                                            <FontAwesomeIcon icon={faServer} className="text-theme-secondary text-xs" />
                                            <span className="text-sm text-theme-primary truncate max-w-[150px]">
                                                {server.name}
                                            </span>
                                        </div>
                                        <button
                                            onClick={() => handleRemoveServerFromGroup(server.uuid, group.id)}
                                            className="text-theme-secondary hover:text-red-400 transition-colors"
                                            title="Remove from group"
                                        >
                                            <FontAwesomeIcon icon={faTimes} size="xs" />
                                        </button>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                ))}

                {/* Create Group Card */}
                <button
                    onClick={openCreateDialog}
                    className="border-2 border-dashed border-theme-border rounded-lg p-4 flex flex-col items-center justify-center gap-2 text-theme-secondary hover:text-theme-primary hover:border-theme-primary transition-colors min-h-[200px]"
                >
                    <FontAwesomeIcon icon={faPlus} size="2x" />
                    <span className="font-medium">Create New Group</span>
                </button>
            </div>

            {/* Ungrouped Servers */}
            {ungroupedServers.length > 0 && (
                <div className="mt-8">
                    <h3 className="text-lg font-semibold text-theme-secondary mb-4 flex items-center gap-2">
                        <FontAwesomeIcon icon={faServer} />
                        Ungrouped Servers
                    </h3>
                    <div className="bg-theme-secondary/5 rounded-lg p-4">
                        <div className="grid gap-2">
                            {ungroupedServers.map(server => (
                                <div
                                    key={server.uuid}
                                    className="flex items-center justify-between bg-theme-background rounded p-3"
                                >
                                    <ServerRow
                                        server={server}
                                        group={undefined}
                                        setOpen={setServerGroupsDialog}
                                    />
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            )}

            {/* Create/Edit Dialog */}
            <Dialog
                open={dialogOpen}
                onClose={() => setDialogOpen(false)}
                title={editingGroup ? 'Edit Server Group' : 'Create Server Group'}
            >
                <div className="space-y-4">
                    <Field
                        label="Group Name"
                        value={groupName}
                        onChange={e => setGroupName(e.target.value)}
                        placeholder="e.g., Production Servers"
                        required
                    />
                    <Field
                        label="Description"
                        value={groupDescription}
                        onChange={e => setGroupDescription(e.target.value)}
                        placeholder="Optional description"
                        type="text"
                    />

                    {/* Color Picker */}
                    <div>
                        <label className="block text-sm font-medium text-theme-secondary mb-2">
                            <FontAwesomeIcon icon={faPalette} className="mr-1" />
                            Group Color
                        </label>
                        <div className="flex flex-wrap gap-2">
                            {PRESET_COLORS.map(color => (
                                <button
                                    key={color}
                                    onClick={() => setGroupColor(color)}
                                    className={`w-8 h-8 rounded-full transition-transform hover:scale-110 ${
                                        groupColor === color ? 'ring-2 ring-offset-2 ring-theme-primary' : ''
                                    }`}
                                    style={{ backgroundColor: color }}
                                    type="button"
                                />
                            ))}
                        </div>
                    </div>

                    {/* Icon Selection */}
                    <div>
                        <label className="block text-sm font-medium text-theme-secondary mb-2">
                            Icon
                        </label>
                        <div className="flex gap-2">
                            {['folder', 'server', 'database', 'cloud', 'star'].map(icon => (
                                <button
                                    key={icon}
                                    onClick={() => setGroupIcon(icon)}
                                    className={`p-2 rounded transition-colors ${
                                        groupIcon === icon
                                            ? 'bg-theme-primary text-white'
                                            : 'bg-theme-secondary/20 text-theme-secondary hover:bg-theme-secondary/30'
                                    }`}
                                    type="button"
                                >
                                    <FontAwesomeIcon icon={icon as any} />
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="flex justify-end gap-3 pt-4">
                        <Button onClick={() => setDialogOpen(false)} type="button" variant="secondary">
                            Cancel
                        </Button>
                        <Button onClick={handleSave} disabled={!groupName.trim()}>
                            {editingGroup ? 'Update Group' : 'Create Group'}
                        </Button>
                    </div>
                </div>
            </Dialog>
        </div>
    );
}
