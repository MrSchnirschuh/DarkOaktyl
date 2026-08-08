import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { api } from '@/lib/api';
import { formatDistanceToNow } from 'date-fns';
import { de } from 'date-fns/locale';
import { AlertTriangle, ArrowUp, ArrowDown, Minus, MessageSquare } from 'lucide-react';
import { TicketFilters } from './TicketFilters';
import { TicketStats } from './TicketStats';

interface Ticket {
    id: number;
    title: string;
    status: string;
    status_label: string;
    category: string;
    category_label: string;
    category_color: string;
    priority: string;
    priority_label: string;
    priority_icon: string;
    user: {
        id: number;
        username: string;
        email: string;
        name: string;
    };
    assigned_to: {
        id: number;
        username: string;
        name: string;
    } | null;
    is_resolved: boolean;
    is_closed: boolean;
    is_open: boolean;
    is_in_progress: boolean;
    created_at: string;
}

interface FilterState {
    status: string | null;
    priority: string | null;
    category: string | null;
    assigned_to: string | null;
    search: string;
}

const PRIORITY_ICONS = {
    critical: AlertTriangle,
    high: ArrowUp,
    medium: Minus,
    low: ArrowDown,
};

const PRIORITY_COLORS = {
    critical: 'text-red-600 bg-red-50',
    high: 'text-orange-500 bg-orange-50',
    medium: 'text-yellow-500 bg-yellow-50',
    low: 'text-green-500 bg-green-50',
};

const STATUS_COLORS = {
    open: 'bg-blue-500',
    in_progress: 'bg-yellow-500',
    resolved: 'bg-green-500',
    closed: 'bg-gray-500',
};

export function TicketList() {
    const navigate = useNavigate();
    const [tickets, setTickets] = useState<Ticket[]>([]);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState<FilterState>({
        status: null,
        priority: null,
        category: null,
        assigned_to: null,
        search: '',
    });

    useEffect(() => {
        fetchTickets();
    }, [filters]);

    const fetchTickets = async () => {
        try {
            setLoading(true);
            const params = new URLSearchParams();
            if (filters.status) params.append('filter[status]', filters.status);
            if (filters.priority) params.append('filter[priority]', filters.priority);
            if (filters.category) params.append('filter[category]', filters.category);
            if (filters.search) params.append('filter[title]', `%${filters.search}%`);

            const response = await api.get(`/api/application/tickets?${params.toString()}`);
            setTickets(response.data.data || []);
        } catch (error) {
            console.error('Failed to fetch tickets:', error);
        } finally {
            setLoading(false);
        }
    };

    const getPriorityIcon = (priority: string) => {
        const Icon = PRIORITY_ICONS[priority] || Minus;
        return Icon;
    };

    const handleTicketClick = (ticketId: number) => {
        navigate(`/admin/tickets/${ticketId}`);
    };

    if (loading) {
        return (
            <div className="space-y-4">
                <TicketStats />
                <TicketFilters onFilterChange={setFilters} />
                {[...Array(3)].map((_, i) => (
                    <Card key={i} className="animate-pulse">
                        <CardContent className="h-24"></CardContent>
                    </Card>
                ))}
            </div>
        );
    }

    return (
        <div className="space-y-4">
            <TicketStats />
            <TicketFilters onFilterChange={setFilters} />

            {tickets.length === 0 ? (
                <Card>
                    <CardContent className="flex flex-col items-center justify-center py-12">
                        <MessageSquare className="h-12 w-12 text-muted-foreground mb-4" />
                        <p className="text-muted-foreground text-lg">Keine Tickets gefunden</p>
                        <p className="text-muted-foreground text-sm">
                            Passen Sie die Filter an oder erstellen Sie ein neues Ticket
                        </p>
                    </CardContent>
                </Card>
            ) : (
                <div className="space-y-2">
                    {tickets.map(ticket => {
                        const PriorityIcon = getPriorityIcon(ticket.priority);

                        return (
                            <Card
                                key={ticket.id}
                                className="cursor-pointer hover:shadow-md transition-shadow"
                                onClick={() => handleTicketClick(ticket.id)}
                            >
                                <CardContent className="p-4">
                                    <div className="flex items-start justify-between gap-4">
                                        {/* Left side - Priority & Status */}
                                        <div className="flex items-center gap-3">
                                            <div className={`p-2 rounded-lg ${PRIORITY_COLORS[ticket.priority]}`}>
                                                <PriorityIcon className="h-5 w-5" />
                                            </div>
                                            <div>
                                                <h3 className="font-semibold text-base">{ticket.title}</h3>
                                                <div className="flex items-center gap-2 mt-1">
                                                    <Badge
                                                        variant="default"
                                                        className={`${STATUS_COLORS[ticket.status]} text-white text-xs`}
                                                    >
                                                        {ticket.status_label}
                                                    </Badge>
                                                    <Badge
                                                        variant="outline"
                                                        className="text-xs"
                                                        style={{
                                                            borderColor: ticket.category_color,
                                                            color: ticket.category_color,
                                                        }}
                                                    >
                                                        {ticket.category_label}
                                                    </Badge>
                                                    <span className="text-xs text-muted-foreground">#{ticket.id}</span>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Right side - User Info & Assignment */}
                                        <div className="flex items-center gap-4 text-right">
                                            <div>
                                                <p className="text-sm font-medium">{ticket.user.name}</p>
                                                <p className="text-xs text-muted-foreground">
                                                    {formatDistanceToNow(new Date(ticket.created_at), {
                                                        addSuffix: true,
                                                        locale: de,
                                                    })}
                                                </p>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                {ticket.assigned_to ? (
                                                    <div className="flex items-center gap-2">
                                                        <Avatar className="h-8 w-8">
                                                            <AvatarFallback className="bg-primary text-primary-foreground text-xs">
                                                                {ticket.assigned_to.username.slice(0, 2).toUpperCase()}
                                                            </AvatarFallback>
                                                        </Avatar>
                                                        <span className="text-sm text-muted-foreground">
                                                            {ticket.assigned_to.username}
                                                        </span>
                                                    </div>
                                                ) : (
                                                    <Badge variant="destructive" className="text-xs">
                                                        Nicht zugewiesen
                                                    </Badge>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
