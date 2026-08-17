import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCube, faSearch, faStar, faFire } from '@fortawesome/free-solid-svg-icons';
import Loader from '@elements/Loader';
import { Button } from '@elements/button';

interface Template {
    id: string;
    name: string;
    description: string;
    type: string;
    type_label: string;
    image?: string;
    default_resources: {
        memory: number;
        cpu: number;
        disk: number;
    };
    is_featured: boolean;
    category: string;
}

interface Category {
    name: string;
    icon: string;
    description?: string;
    templates: Template[];
}

function TemplateCard({ template }: { template: Template }) {
    return (
        <Link
            to={`/templates/${template.id}`}
            className="group block p-6 bg-white dark:bg-gray-800 border rounded-lg hover:shadow-lg transition-all"
        >
            <div className="flex items-start gap-4">
                {template.image ? (
                    <img src={template.image} alt={template.name} className="w-16 h-16 rounded-lg object-cover" />
                ) : (
                    <div className="w-16 h-16 rounded-lg bg-blue-100 flex items-center justify-center">
                        <FontAwesomeIcon icon={faCube} className="text-2xl text-blue-500" />
                    </div>
                )}

                <div className="flex-1">
                    <div className="flex items-center gap-2">
                        <h3 className="font-semibold">{template.name}</h3>
                        {template.is_featured && <FontAwesomeIcon icon={faStar} className="text-yellow-500 text-sm" />}
                    </div>
                    <p className="text-sm text-gray-500">{template.category}</p>
                    <p className="text-sm text-gray-600 dark:text-gray-400 mt-2 line-clamp-2">{template.description}</p>

                    <div className="mt-3 flex items-center gap-3 text-xs text-gray-500">
                        <span>{template.default_resources.memory}MB RAM</span>
                        <span>•</span>
                        <span>{template.default_resources.disk / 1024}GB Disk</span>
                    </div>
                </div>
            </div>
        </Link>
    );
}

export default function TemplateGallery() {
    const [categories, setCategories] = useState<Category[]>([]);
    const [featured, setFeatured] = useState<Template[]>([]);
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedType, setSelectedType] = useState<string | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const fetchTemplates = async () => {
        try {
            const response = await fetch('/api/client/templates');
            if (!response.ok) throw new Error('Failed to fetch templates');
            const data = await response.json();
            setCategories(data.data || []);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Failed to load templates');
        } finally {
            setIsLoading(false);
        }
    };

    const fetchFeatured = async () => {
        try {
            const response = await fetch('/api/client/templates/featured');
            if (response.ok) {
                const data = await response.json();
                setFeatured(data.data || []);
            }
        } catch (err) {
            console.error('Failed to fetch featured:', err);
        }
    };

    useEffect(() => {
        fetchTemplates();
        fetchFeatured();
    }, []);

    const handleSearch = async () => {
        if (searchQuery.length < 2) return;

        try {
            const response = await fetch(`/api/client/templates/search?q=${encodeURIComponent(searchQuery)}`);
            if (response.ok) {
                const data = await response.json();
                // Create a single category for search results
                setCategories([{ name: 'Search Results', icon: 'faSearch', templates: data.data || [] }]);
            }
        } catch (err) {
            console.error('Search failed:', err);
        }
    };

    const filterByType = async (type: string) => {
        setSelectedType(type);
        try {
            const response = await fetch(`/api/client/templates/type/${type}`);
            if (response.ok) {
                const data = await response.json();
                setCategories([
                    {
                        name: `${type.charAt(0).toUpperCase() + type.slice(1)} Templates`,
                        icon: 'faGamepad',
                        templates: data.data || [],
                    },
                ]);
            }
        } catch (err) {
            console.error('Failed to filter:', err);
        }
    };

    if (isLoading) {
        return (
            <div className="flex items-center justify-center h-64">
                <Loader size="large" />
            </div>
        );
    }

    if (error) {
        return <div className="p-4 bg-red-50 border border-red-200 rounded-lg text-red-600">{error}</div>;
    }

    return (
        <div className="space-y-8">
            <div className="text-center space-y-4">
                <h1 className="text-3xl font-bold">One-Click Apps</h1>
                <p className="text-gray-500 max-w-2xl mx-auto">
                    Deploy your favorite game servers and applications with a single click. Pre-configured and ready to
                    go.
                </p>

                <div className="flex items-center gap-2 max-w-md mx-auto">
                    <div className="relative flex-1">
                        <FontAwesomeIcon
                            icon={faSearch}
                            className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
                        />
                        <input
                            type="text"
                            placeholder="Search templates..."
                            value={searchQuery}
                            onChange={e => setSearchQuery(e.target.value)}
                            onKeyPress={e => e.key === 'Enter' && handleSearch()}
                            className="w-full pl-10 pr-4 py-2 border rounded-lg"
                        />
                    </div>
                    <Button variant="primary" onClick={handleSearch}>
                        Search
                    </Button>
                </div>

                <div className="flex flex-wrap justify-center gap-2">
                    {['minecraft', 'valheim', 'cs2', 'rust', 'gmod'].map(type => (
                        <button
                            key={type}
                            onClick={() => filterByType(type)}
                            className={`px-4 py-2 rounded-full text-sm font-medium transition-colors ${
                                selectedType === type
                                    ? 'bg-blue-500 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                            }`}
                        >
                            {type.charAt(0).toUpperCase() + type.slice(1)}
                        </button>
                    ))}
                </div>
            </div>

            {featured.length > 0 && !selectedType && (
                <div>
                    <h2 className="text-xl font-semibold flex items-center gap-2 mb-4">
                        <FontAwesomeIcon icon={faFire} className="text-orange-500" />
                        Featured
                    </h2>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {featured.map(template => (
                            <TemplateCard key={template.id} template={template} />
                        ))}
                    </div>
                </div>
            )}

            {categories.map(category => (
                <div key={category.name}>
                    <h2 className="text-xl font-semibold mb-4">{category.name}</h2>
                    {category.templates.length === 0 ? (
                        <p className="text-gray-500">No templates available</p>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {category.templates.map(template => (
                                <TemplateCard key={template.id} template={template} />
                            ))}
                        </div>
                    )}
                </div>
            ))}
        </div>
    );
}
