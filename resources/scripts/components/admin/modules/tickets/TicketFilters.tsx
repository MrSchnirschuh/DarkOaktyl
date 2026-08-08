import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Search, X } from 'lucide-react';

interface TicketFiltersProps {
    onFilterChange: (filters: FilterState) => void;
}

interface FilterState {
    status: string | null;
    priority: string | null;
    category: string | null;
    assigned_to: string | null;
    search: string;
}

const STATUS_OPTIONS = [
    { value: 'open', label: 'Offen', color: 'bg-blue-500' },
    { value: 'in_progress', label: 'In Bearbeitung', color: 'bg-yellow-500' },
    { value: 'resolved', label: 'Gelöst', color: 'bg-green-500' },
    { value: 'closed', label: 'Geschlossen', color: 'bg-gray-500' },
];

const PRIORITY_OPTIONS = [
    { value: 'critical', label: 'Kritisch', icon: 'alert-triangle', color: 'text-red-600' },
    { value: 'high', label: 'Hoch', icon: 'arrow-up', color: 'text-orange-500' },
    { value: 'medium', label: 'Mittel', icon: 'minus', color: 'text-yellow-500' },
    { value: 'low', label: 'Niedrig', icon: 'arrow-down', color: 'text-green-500' },
];

const CATEGORY_OPTIONS = [
    { value: 'technical', label: 'Technisch', color: '#3B82F6' },
    { value: 'billing', label: 'Abrechnung', color: '#10B981' },
    { value: 'general', label: 'Allgemein', color: '#6B7280' },
];

export function TicketFilters({ onFilterChange }: TicketFiltersProps) {
    const [filters, setFilters] = useState<FilterState>({
        status: null,
        priority: null,
        category: null,
        assigned_to: null,
        search: '',
    });

    const handleFilterChange = (key: keyof FilterState, value: string | null) => {
        const newFilters = { ...filters, [key]: value };
        setFilters(newFilters);
        onFilterChange(newFilters);
    };

    const clearFilters = () => {
        const cleared = {
            status: null,
            priority: null,
            category: null,
            assigned_to: null,
            search: '',
        };
        setFilters(cleared);
        onFilterChange(cleared);
    };

    const hasActiveFilters = Object.values(filters).some(v => v !== null && v !== '');

    return (
        <div className="space-y-3 mb-4">
            <div className="flex flex-wrap gap-2 items-center">
                {/* Search */}
                <div className="relative flex-1 min-w-[200px]">
                    <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                        placeholder="Tickets suchen..."
                        value={filters.search}
                        onChange={e => handleFilterChange('search', e.target.value)}
                        className="pl-8"
                    />
                </div>

                {/* Status Filter */}
                <Select
                    value={filters.status || undefined}
                    onValueChange={value => handleFilterChange('status', value || null)}
                >
                    <SelectTrigger className="w-[150px]">
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        {STATUS_OPTIONS.map(option => (
                            <SelectItem key={option.value} value={option.value}>
                                <div className="flex items-center gap-2">
                                    <span className={`w-2 h-2 rounded-full ${option.color}`} />
                                    {option.label}
                                </div>
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                {/* Priority Filter */}
                <Select
                    value={filters.priority || undefined}
                    onValueChange={value => handleFilterChange('priority', value || null)}
                >
                    <SelectTrigger className="w-[150px]">
                        <SelectValue placeholder="Priorität" />
                    </SelectTrigger>
                    <SelectContent>
                        {PRIORITY_OPTIONS.map(option => (
                            <SelectItem key={option.value} value={option.value}>
                                <div className="flex items-center gap-2">
                                    <span className={option.color}>{option.label}</span>
                                </div>
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                {/* Category Filter */}
                <Select
                    value={filters.category || undefined}
                    onValueChange={value => handleFilterChange('category', value || null)}
                >
                    <SelectTrigger className="w-[150px]">
                        <SelectValue placeholder="Kategorie" />
                    </SelectTrigger>
                    <SelectContent>
                        {CATEGORY_OPTIONS.map(option => (
                            <SelectItem key={option.value} value={option.value}>
                                <div className="flex items-center gap-2">
                                    <span className="w-2 h-2 rounded-full" style={{ backgroundColor: option.color }} />
                                    {option.label}
                                </div>
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                {/* Clear Filters */}
                {hasActiveFilters && (
                    <Button variant="ghost" size="sm" onClick={clearFilters}>
                        <X className="h-4 w-4 mr-1" />
                        Filter zurücksetzen
                    </Button>
                )}
            </div>

            {/* Active Filter Pills */}
            {hasActiveFilters && (
                <div className="flex flex-wrap gap-2">
                    {filters.status && (
                        <Badge
                            variant="secondary"
                            className="cursor-pointer"
                            onClick={() => handleFilterChange('status', null)}
                        >
                            Status: {STATUS_OPTIONS.find(s => s.value === filters.status)?.label}
                            <X className="h-3 w-3 ml-1" />
                        </Badge>
                    )}
                    {filters.priority && (
                        <Badge
                            variant="secondary"
                            className="cursor-pointer"
                            onClick={() => handleFilterChange('priority', null)}
                        >
                            Priorität: {PRIORITY_OPTIONS.find(p => p.value === filters.priority)?.label}
                            <X className="h-3 w-3 ml-1" />
                        </Badge>
                    )}
                    {filters.category && (
                        <Badge
                            variant="secondary"
                            className="cursor-pointer"
                            onClick={() => handleFilterChange('category', null)}
                        >
                            Kategorie: {CATEGORY_OPTIONS.find(c => c.value === filters.category)?.label}
                            <X className="h-3 w-3 ml-1" />
                        </Badge>
                    )}
                    {filters.search && (
                        <Badge
                            variant="secondary"
                            className="cursor-pointer"
                            onClick={() => handleFilterChange('search', '')}
                        >
                            Suche: {filters.search}
                            <X className="h-3 w-3 ml-1" />
                        </Badge>
                    )}
                </div>
            )}
        </div>
    );
}
