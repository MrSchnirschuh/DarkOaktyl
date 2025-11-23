import classNames from 'classnames';
import Spinner from '@elements/Spinner';
import { Button } from '@elements/button';
import { useStoreState } from '@/state/hooks';
import ContentBox from '@elements/ContentBox';
import { ReactElement, useEffect, useState } from 'react';
import PageContentBlock from '@elements/PageContentBlock';
import { getProducts, Product } from '@/api/billing/products';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import getCategories, { Category } from '@/api/billing/getCategories';
import {
    IconDefinition,
    faArchive,
    faDatabase,
    faEthernet,
    faExclamationTriangle,
    faHdd,
    faMemory,
    faMicrochip,
    faShoppingBag,
} from '@fortawesome/free-solid-svg-icons';
import { Link } from 'react-router-dom';
import { Alert } from '@elements/alert';
import BuilderStorefront from '@/components/billing/builder/BuilderStorefront';

interface LimitProps {
    icon: IconDefinition;
    limit: ReactElement;
}

const LimitBox = ({ icon, limit }: LimitProps) => (
    <div className={'text-theme-muted mt-1'}>
        <FontAwesomeIcon icon={icon} className={'w-4 h-4 mr-2'} />
        {limit}
    </div>
);

const LegacyProductStorefront = () => {
    const [category, setCategory] = useState<number>();
    const [products, setProducts] = useState<Product[] | undefined>();
    const [categories, setCategories] = useState<Category[] | undefined>();
    const [categoriesLoading, setCategoriesLoading] = useState<boolean>(true);
    const [categoriesError, setCategoriesError] = useState<boolean>(false);

    const settings = useStoreState(s => s.DarkOak.data!.billing);
    const { colors } = useStoreState(state => state.theme.data!);

    useEffect(() => {
        let isMounted = true;

        const fetchCategories = async () => {
            try {
                setCategoriesLoading(true);
                setCategoriesError(false);
                const data = await getCategories();

                if (!isMounted) return;

                setCategories(data);
                const [firstCategory] = data;
                if (firstCategory) {
                    setCategory(Number(firstCategory.id));
                } else {
                    setCategory(undefined);
                }
            } catch (error) {
                console.error('Error loading billing categories:', error);
                if (isMounted) {
                    setCategories([]);
                    setCategory(undefined);
                    setCategoriesError(true);
                }
            } finally {
                if (isMounted) {
                    setCategoriesLoading(false);
                }
            }
        };

        fetchCategories();

        return () => {
            isMounted = false;
        };
    }, []);

    useEffect(() => {
        let isMounted = true;

        if (!category) {
            setProducts([]);
            return () => {
                isMounted = false;
            };
        }

        setProducts(undefined);

        const fetchProducts = async () => {
            try {
                const data = await getProducts(category);
                if (isMounted) {
                    setProducts(data);
                }
            } catch (error) {
                console.error('Error loading billing products:', error);
                if (isMounted) {
                    setProducts([]);
                }
            }
        };

        fetchProducts();

        return () => {
            isMounted = false;
        };
    }, [category]);

    return (
        <PageContentBlock title={'Available Products'}>
            <div className={'text-3xl lg:text-5xl font-bold mt-8 mb-12'}>
                Order a Product
                <p className={'text-theme-muted font-normal text-sm mt-1'}>
                    Choose and configure any of the products below to your liking.
                </p>
            </div>
            <div className={'grid lg:grid-cols-4 gap-4 lg:gap-12'}>
                <div className={'border-r-4 border-gray-500'}>
                    <p className={'text-2xl text-theme-secondary mb-8 mt-4 font-bold'}>Categories</p>
                    {categoriesLoading ? (
                        <div className={'flex justify-center py-4'}>
                            <Spinner size={'small'} />
                        </div>
                    ) : (
                        <>
                            {categoriesError && (
                                <Alert type={'danger'}>
                                    We were unable to load product categories. Please refresh the page or try again
                                    later.
                                </Alert>
                            )}
                            {!categoriesError && (categories?.length ?? 0) < 1 && (
                                <div className={'font-semibold my-4 text-theme-muted'}>
                                    <FontAwesomeIcon
                                        icon={faExclamationTriangle}
                                        className={'w-5 h-5 mr-2 text-yellow-400'}
                                    />
                                    No categories found.
                                </div>
                            )}
                            {categories?.map(cat => (
                                <button
                                    className={classNames(
                                        'font-semibold my-4 w-full text-left hover:brightness-150 duration-300 cursor-pointer line-clamp-1',
                                        Number(cat.id) === category && 'brightness-150',
                                    )}
                                    disabled={category === Number(cat.id)}
                                    style={{ color: colors.primary }}
                                    onClick={() => {
                                        setCategory(Number(cat.id));
                                        setProducts(undefined);
                                    }}
                                    key={cat.id}
                                >
                                    {cat.icon && (
                                        <img src={cat.icon} className={'w-7 h-7 inline-flex rounded-full mr-3'} />
                                    )}
                                    {cat.name}
                                    <div className={'h-0.5 mt-4 bg-gray-600 mr-8 rounded-full'} />
                                </button>
                            ))}
                        </>
                    )}
                </div>
                <div className={'lg:col-span-3'}>
                    {!products ? (
                        <Spinner centered />
                    ) : (
                        <>
                            {products?.length < 1 && (
                                <div className={'font-semibold my-4 text-theme-muted'}>
                                    <FontAwesomeIcon
                                        icon={faExclamationTriangle}
                                        className={'w-5 h-5 mr-2 text-yellow-400'}
                                    />
                                    No products could be found in this category.
                                </div>
                            )}
                            <div className={'grid grid-cols-1 xl:grid-cols-3 gap-4'}>
                                {products?.map(product => (
                                    <ContentBox key={product.id}>
                                        <div className={'p-3 lg:p-6'}>
                                            <div className={'flex justify-center'}>
                                                {product.icon ? (
                                                    <img src={product.icon} className={'w-16 h-16'} />
                                                ) : (
                                                    <FontAwesomeIcon
                                                        icon={faShoppingBag}
                                                        className={'w-12 h-12 m-2'}
                                                        style={{ color: colors.primary }}
                                                    />
                                                )}
                                            </div>
                                            <p className={'text-3xl font-bold text-center mt-3'}>{product.name}</p>
                                            <p
                                                className={
                                                    'text-lg font-semibold text-center mt-1 mb-4 text-theme-muted'
                                                }
                                            >
                                                <span style={{ color: colors.primary }} className={'mr-1'}>
                                                    {settings.currency.symbol}
                                                    {product.price.toFixed(2)}
                                                    &nbsp;
                                                    {settings.currency.code.toUpperCase()}
                                                </span>
                                                <span className={'text-base'}>/ monthly</span>
                                            </p>
                                            <div className={'grid justify-center items-center'}>
                                                <LimitBox icon={faMicrochip} limit={<>{product.limits.cpu}% CPU</>} />
                                                <LimitBox
                                                    icon={faMemory}
                                                    limit={<>{product.limits.memory / 1024} GiB of RAM</>}
                                                />
                                                <LimitBox
                                                    icon={faHdd}
                                                    limit={<>{product.limits.disk / 1024} GiB of Storage</>}
                                                />
                                                <div className={'border border-dashed border-gray-500 my-4'} />
                                                {product.limits.backup ? (
                                                    <LimitBox
                                                        icon={faArchive}
                                                        limit={<>{product.limits.backup} backup slots</>}
                                                    />
                                                ) : (
                                                    <></>
                                                )}
                                                {product.limits.database ? (
                                                    <LimitBox
                                                        icon={faDatabase}
                                                        limit={<>{product.limits.database} database slots</>}
                                                    />
                                                ) : (
                                                    <></>
                                                )}
                                                <LimitBox
                                                    icon={faEthernet}
                                                    limit={
                                                        <>
                                                            {product.limits.allocation} network port
                                                            {product.limits.allocation > 1 && 's'}
                                                        </>
                                                    }
                                                />
                                            </div>
                                            <div className={'text-center mt-6'}>
                                                <Link to={`/account/billing/order/${product.id}`}>
                                                    <Button size={Button.Sizes.Large} className={'w-full'}>
                                                        Configure
                                                    </Button>
                                                </Link>
                                            </div>
                                        </div>
                                    </ContentBox>
                                ))}
                            </div>
                        </>
                    )}
                </div>
            </div>
        </PageContentBlock>
    );
};

const StorefrontSwitcher = () => {
    const billingSettings = useStoreState(s => s.DarkOak.data!.billing);
    const [view, setView] = useState<'products' | 'builder'>(
        billingSettings.storefrontMode === 'builder' ? 'builder' : 'products',
    );

    const showBuilder = billingSettings.storefrontMode !== 'products';
    const showProducts = billingSettings.storefrontMode !== 'builder';

    useEffect(() => {
        if (!showProducts) {
            setView('builder');
        } else if (!showBuilder) {
            setView('products');
        }
    }, [showProducts, showBuilder]);

    if (!billingSettings.keys.publishable) {
        return (
            <Alert type={'danger'}>
                Due to a configuration error, the store is currently unavailable. Please try again later, or refresh the
                page.
            </Alert>
        );
    }

    if (showBuilder && !showProducts) {
        return <BuilderStorefront />;
    }

    if (!showBuilder && showProducts) {
        return <LegacyProductStorefront />;
    }

    return (
        <>
            <div className={'flex justify-center gap-4 mt-6'}>
                <button
                    className={classNames(
                        'px-6 py-2 rounded-full border border-dark-400 text-sm font-semibold transition-colors',
                        view === 'products' ? 'bg-theme-primary text-black' : 'bg-dark-500 text-theme-muted',
                    )}
                    onClick={() => setView('products')}
                >
                    Products
                </button>
                <button
                    className={classNames(
                        'px-6 py-2 rounded-full border border-dark-400 text-sm font-semibold transition-colors',
                        view === 'builder' ? 'bg-theme-primary text-black' : 'bg-dark-500 text-theme-muted',
                    )}
                    onClick={() => setView('builder')}
                >
                    Builder
                </button>
            </div>
            {view === 'builder' ? <BuilderStorefront /> : <LegacyProductStorefront />}
        </>
    );
};

export default StorefrontSwitcher;
