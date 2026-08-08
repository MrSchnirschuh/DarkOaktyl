import { useState, useEffect } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { api } from '@/lib/api';
import { Gamepad2, Search, Zap, Cpu, HardDrive, MemoryStick, ChevronRight, Star } from 'lucide-react';
import { TemplateDeployModal } from './TemplateDeployModal';

interface Template {
    uuid: string;
    name: string;
    description: string | null;
    type: string;
    type_label: string;
    image: string | null;
    is_featured: boolean;
    default_resources: {
        memory: number;
        swap: number;
        disk: number;
        cpu: number;
        io: number;
    };
    feature_limits: {
        databases: number;
        allocations: number;
        backups: number;
    };
    category?: {
        uuid: string;
        name: string;
    };
}

interface Category {
    uuid: string;
    name: string;
    description: string | null;
    icon: string | null;
    templates: Template[];
}

const GAME_ICONS: Record<string, string> = {
    minecraft: '🎮',
    valheim: '🪓',
    cs2: '🔫',
    gmod: '🧰',
    rust: '⚙️',
    factorio: '🏭',
    terraria: '🌍',
    other: '🎯',
};

const TYPE_COLORS: Record<string, string> = {
    minecraft: 'bg-green-500/10 text-green-600 border-green-500/20',
    valheim: 'bg-amber-500/10 text-amber-600 border-amber-500/20',
    cs2: 'bg-orange-500/10 text-orange-600 border-orange-500/20',
    gmod: 'bg-blue-500/10 text-blue-600 border-blue-500/20',
    rust: 'bg-red-500/10 text-red-600 border-red-500/20',
    factorio: 'bg-yellow-500/10 text-yellow-600 border-yellow-500/20',
    terraria: 'bg-purple-500/10 text-purple-600 border-purple-500/20',
    other: 'bg-gray-500/10 text-gray-600 border-gray-500/20',
};

export function TemplateStore() {
    const [categories, setCategories] = useState<Category[]>([]);
    const [featured, setFeatured] = useState<Template[]>([]);
    const [loading, setLoading] = useState(true);
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedType, setSelectedType] = useState<string | null>(null);
    const [selectedTemplate, setSelectedTemplate] = useState<Template | null>(null);
    const [isDeployModalOpen, setIsDeployModalOpen] = useState(false);

    useEffect(() => {
        fetchTemplates();
    }, []);

    const fetchTemplates = async () => {
        try {
            setLoading(true);
            const response = await api.get('/api/client/store/templates');
            setCategories(response.data.data.categories || []);
            setFeatured(response.data.data.featured || []);
        } catch (error) {
            console.error('Failed to fetch templates:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleDeploy = (template: Template) => {
        setSelectedTemplate(template);
        setIsDeployModalOpen(true);
    };

    const formatMemory = (mb: number) => {
        if (mb >= 1024) return `${(mb / 1024).toFixed(1)} GB`;
        return `${mb} MB`;
    };

    const formatDisk = (mb: number) => {
        if (mb >= 1024) return `${(mb / 1024).toFixed(1)} GB`;
        return `${mb} MB`;
    };

    const allTemplates = categories.flatMap(c => c.templates);
    const filteredTemplates = searchQuery
        ? allTemplates.filter(
              t =>
                  t.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
                  t.type_label.toLowerCase().includes(searchQuery.toLowerCase()),
          )
        : allTemplates;

    const displayedCategories = selectedType
        ? categories
              .map(c => ({
                  ...c,
                  templates: c.templates.filter(t => t.type === selectedType),
              }))
              .filter(c => c.templates.length > 0)
        : categories;

    const availableTypes = Array.from(new Set(allTemplates.map(t => t.type)));

    if (loading) {
        return (
            <div className="space-y-8">
                <div className="flex items-center justify-between">
                    <div className="h-8 w-64 bg-muted animate-pulse rounded" />
                    <div className="h-10 w-80 bg-muted animate-pulse rounded" />
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {[...Array(6)].map((_, i) => (
                        <Card key={i} className="animate-pulse">
                            <CardContent className="h-48" />
                        </Card>
                    ))}
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-8">
            {/* Header */}
            <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 className="text-3xl font-bold flex items-center gap-3">
                        <Gamepad2 className="h-8 w-8 text-primary" />
                        Server Templates
                    </h1>
                    <p className="text-muted-foreground mt-1">Deploy your favorite game servers with one click</p>
                </div>
                <div className="relative w-full md:w-80">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                        placeholder="Search templates..."
                        value={searchQuery}
                        onChange={e => setSearchQuery(e.target.value)}
                        className="pl-10"
                    />
                </div>
            </div>

            {/* Featured Templates */}
            {!searchQuery && featured.length > 0 && (
                <div className="space-y-4">
                    <div className="flex items-center gap-2">
                        <Zap className="h-5 w-5 text-yellow-500" />
                        <h2 className="text-xl font-semibold">Featured</h2>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {featured.map(template => (
                            <TemplateCard
                                key={template.uuid}
                                template={template}
                                onDeploy={handleDeploy}
                                formatMemory={formatMemory}
                                formatDisk={formatDisk}
                                featured
                            />
                        ))}
                    </div>
                </div>
            )}

            {/* Type Filter */}
            {!searchQuery && (
                <div className="flex flex-wrap gap-2">
                    <Button
                        variant={selectedType === null ? 'default' : 'outline'}
                        size="sm"
                        onClick={() => setSelectedType(null)}
                    >
                        All
                    </Button>
                    {availableTypes.map(type => (
                        <Button
                            key={type}
                            variant={selectedType === type ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => setSelectedType(selectedType === type ? null : type)}
                        >
                            <span className="mr-1">{GAME_ICONS[type] || '🎯'}</span>
                            {type.charAt(0).toUpperCase() + type.slice(1)}
                        </Button>
                    ))}
                </div>
            )}

            {/* Templates by Category */}
            {searchQuery ? (
                <div className="space-y-4">
                    <h2 className="text-xl font-semibold">Search Results</h2>
                    {filteredTemplates.length === 0 ? (
                        <EmptyState />
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {filteredTemplates.map(template => (
                                <TemplateCard
                                    key={template.uuid}
                                    template={template}
                                    onDeploy={handleDeploy}
                                    formatMemory={formatMemory}
                                    formatDisk={formatDisk}
                                />
                            ))}
                        </div>
                    )}
                </div>
            ) : (
                <div className="space-y-8">
                    {displayedCategories.map(category => (
                        <div key={category.uuid} className="space-y-4">
                            <div className="flex items-center gap-2">
                                <span className="text-lg">{category.icon || '📁'}</span>
                                <h2 className="text-xl font-semibold">{category.name}</h2>
                            </div>
                            {category.description && (
                                <p className="text-muted-foreground -mt-2">{category.description}</p>
                            )}
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                {category.templates.map(template => (
                                    <TemplateCard
                                        key={template.uuid}
                                        template={template}
                                        onDeploy={handleDeploy}
                                        formatMemory={formatMemory}
                                        formatDisk={formatDisk}
                                    />
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {displayedCategories.length === 0 && !searchQuery && <EmptyState />}

            {/* Deploy Modal */}
            {selectedTemplate && (
                <TemplateDeployModal
                    template={selectedTemplate}
                    isOpen={isDeployModalOpen}
                    onClose={() => {
                        setIsDeployModalOpen(false);
                        setSelectedTemplate(null);
                    }}
                    onSuccess={() => {
                        setIsDeployModalOpen(false);
                        setSelectedTemplate(null);
                        // Optionally refresh or navigate
                    }}
                />
            )}
        </div>
    );
}

interface TemplateCardProps {
    template: Template;
    onDeploy: (template: Template) => void;
    formatMemory: (mb: number) => string;
    formatDisk: (mb: number) => string;
    featured?: boolean;
}

function TemplateCard({ template, onDeploy, formatMemory, formatDisk, featured }: TemplateCardProps) {
    return (
        <Card
            className={`group overflow-hidden transition-all hover:shadow-lg ${featured ? 'border-yellow-500/30' : ''}`}
        >
            <CardContent className="p-0">
                {/* Image or Icon Header */}
                <div
                    className={`h-24 flex items-center justify-center relative ${
                        TYPE_COLORS[template.type] || TYPE_COLORS.other
                    }`}
                >
                    {featured && (
                        <div className="absolute top-2 right-2">
                            <Star className="h-5 w-5 text-yellow-500 fill-yellow-500" />
                        </div>
                    )}
                    <span className="text-5xl">{GAME_ICONS[template.type] || '🎯'}</span>
                </div>

                {/* Content */}
                <div className="p-4 space-y-3">
                    <div className="flex items-start justify-between gap-2">
                        <div>
                            <h3 className="font-semibold text-lg">{template.name}</h3>
                            <Badge variant="outline" className="mt-1 text-xs">
                                {template.type_label}
                            </Badge>
                        </div>
                    </div>

                    {template.description && (
                        <p className="text-sm text-muted-foreground line-clamp-2">{template.description}</p>
                    )}

                    {/* Resource Summary */}
                    <div className="flex flex-wrap gap-3 text-xs text-muted-foreground pt-2">
                        <div className="flex items-center gap-1">
                            <MemoryStick className="h-3 w-3" />
                            {formatMemory(template.default_resources.memory)}
                        </div>
                        <div className="flex items-center gap-1">
                            <HardDrive className="h-3 w-3" />
                            {formatDisk(template.default_resources.disk)}
                        </div>
                        <div className="flex items-center gap-1">
                            <Cpu className="h-3 w-3" />
                            {template.default_resources.cpu}%
                        </div>
                    </div>

                    {/* Deploy Button */}
                    <Button className="w-full mt-2 group-hover:bg-primary/90" onClick={() => onDeploy(template)}>
                        Deploy Now
                        <ChevronRight className="ml-1 h-4 w-4" />
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}

function EmptyState() {
    return (
        <Card>
            <CardContent className="flex flex-col items-center justify-center py-12">
                <Gamepad2 className="h-12 w-12 text-muted-foreground mb-4" />
                <p className="text-muted-foreground text-lg">No templates found</p>
                <p className="text-muted-foreground text-sm">Try adjusting your search or filters</p>
            </CardContent>
        </Card>
    );
}
