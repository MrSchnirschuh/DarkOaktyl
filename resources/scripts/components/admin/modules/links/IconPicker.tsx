import { useMemo } from 'react';
import tw from 'twin.macro';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faGlobe,
    faLink,
    faExternalLinkAlt,
    faShoppingCart,
    faQuestionCircle,
    faInfoCircle,
    faStar,
    faHeart,
    faFire,
    faBolt,
    faBell,
    faEnvelope,
    faComment,
    faUser,
    faCog,
    faLock,
    faKey,
    faShieldAlt,
    faDownload,
    faUpload,
    faCloud,
    faServer,
    faDatabase,
    faCode,
    faTerminal,
    faBook,
    faFile,
    faImage,
    faVideo,
    faMusic,
    faGamepad,
    faPhone,
    faHome,
    faSearch,
    faChartBar,
    faCalendar,
    faClock,
    faMapMarker,
    faMoneyBill,
    faCreditCard,
    faGift,
    faTag,
    faCrown,
    faRocket,
} from '@fortawesome/free-solid-svg-icons';
import {
    faDiscord,
    faGithub,
    faTwitter,
    faYoutube,
    faTwitch,
    faReddit,
    faTelegram,
    faWhatsapp,
    faSlack,
    faMedium,
} from '@fortawesome/free-brands-svg-icons';

interface IconPickerProps {
    value: string | null;
    onChange: (icon: string) => void;
}

const allIcons = [
    // Solid
    { name: 'globe', icon: faGlobe },
    { name: 'link', icon: faLink },
    { name: 'external-link', icon: faExternalLinkAlt },
    { name: 'shopping-cart', icon: faShoppingCart },
    { name: 'question', icon: faQuestionCircle },
    { name: 'info', icon: faInfoCircle },
    { name: 'star', icon: faStar },
    { name: 'heart', icon: faHeart },
    { name: 'fire', icon: faFire },
    { name: 'bolt', icon: faBolt },
    { name: 'bell', icon: faBell },
    { name: 'envelope', icon: faEnvelope },
    { name: 'comment', icon: faComment },
    { name: 'user', icon: faUser },
    { name: 'cog', icon: faCog },
    { name: 'lock', icon: faLock },
    { name: 'key', icon: faKey },
    { name: 'shield', icon: faShieldAlt },
    { name: 'download', icon: faDownload },
    { name: 'upload', icon: faUpload },
    { name: 'cloud', icon: faCloud },
    { name: 'server', icon: faServer },
    { name: 'database', icon: faDatabase },
    { name: 'code', icon: faCode },
    { name: 'terminal', icon: faTerminal },
    { name: 'book', icon: faBook },
    { name: 'file', icon: faFile },
    { name: 'image', icon: faImage },
    { name: 'video', icon: faVideo },
    { name: 'music', icon: faMusic },
    { name: 'gamepad', icon: faGamepad },
    { name: 'phone', icon: faPhone },
    { name: 'home', icon: faHome },
    { name: 'search', icon: faSearch },
    { name: 'chart', icon: faChartBar },
    { name: 'calendar', icon: faCalendar },
    { name: 'clock', icon: faClock },
    { name: 'location', icon: faMapMarker },
    { name: 'money', icon: faMoneyBill },
    { name: 'credit-card', icon: faCreditCard },
    { name: 'gift', icon: faGift },
    { name: 'tag', icon: faTag },
    { name: 'crown', icon: faCrown },
    { name: 'rocket', icon: faRocket },
    // Brands
    { name: 'discord', icon: faDiscord },
    { name: 'github', icon: faGithub },
    { name: 'twitter', icon: faTwitter },
    { name: 'youtube', icon: faYoutube },
    { name: 'twitch', icon: faTwitch },
    { name: 'reddit', icon: faReddit },
    { name: 'telegram', icon: faTelegram },
    { name: 'whatsapp', icon: faWhatsapp },
    { name: 'slack', icon: faSlack },
    { name: 'medium', icon: faMedium },
];

const iconNameMap: Record<string, typeof faGlobe> = {};
allIcons.forEach(({ name, icon }) => {
    iconNameMap[name] = icon;
});

export const getIconByName = (name: string | null) => {
    if (!name) return null;
    const resolved = iconNameMap[name];
    return resolved ?? null;
};

const IconPicker = ({ value, onChange }: IconPickerProps) => {
    return (
        <div>
            <label
                css={tw`text-xs font-semibold uppercase tracking-wide`}
                style={{ color: 'var(--theme-text-muted, #64748b)' }}
            >
                Icon (optional)
            </label>
            <div
                css={tw`flex flex-wrap gap-2 mt-2 max-h-48 overflow-y-auto p-2 rounded-lg`}
                style={{ backgroundColor: 'var(--theme-surface-card, #1e293b)' }}
            >
                <button
                    type="button"
                    onClick={() => onChange('')}
                    css={tw`w-9 h-9 flex items-center justify-center rounded transition-colors text-xs`}
                    style={{
                        backgroundColor: !value ? 'var(--theme-accent, #22c55e)' : 'transparent',
                        color: !value ? '#fff' : 'var(--theme-text-muted, #64748b)',
                        border: '1px solid var(--theme-surface-card, #334155)',
                    }}
                    title="No icon"
                >
                    ∅
                </button>
                {allIcons.map(({ name, icon }) => (
                    <button
                        key={name}
                        type="button"
                        onClick={() => onChange(name)}
                        css={tw`w-9 h-9 flex items-center justify-center rounded transition-colors`}
                        style={{
                            backgroundColor: value === name ? 'var(--theme-accent, #22c55e)' : 'transparent',
                            color: value === name ? '#fff' : 'var(--theme-text-secondary, #94a3b8)',
                            border: '1px solid var(--theme-surface-card, #334155)',
                        }}
                        title={name}
                    >
                        <FontAwesomeIcon icon={icon} css={tw`text-sm`} />
                    </button>
                ))}
            </div>
        </div>
    );
};

export default IconPicker;
