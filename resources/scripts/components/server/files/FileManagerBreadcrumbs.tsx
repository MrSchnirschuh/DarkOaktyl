import { Fragment, useEffect, useState } from 'react';
import { NavLink, useParams } from 'react-router-dom';
import tw from 'twin.macro';

import { encodePathSegments } from '@/helpers';
import { ServerContext } from '@/state/server';

interface Props {
    renderLeft?: JSX.Element;
    withinFileEditor?: boolean;
    isNewFile?: boolean;
}

export default ({ renderLeft, withinFileEditor, isNewFile }: Props) => {
    const id = ServerContext.useStoreState(state => state.server.data!.id);
    const directory = ServerContext.useStoreState(state => state.files.directory);

    const params = useParams<'*'>();

    const [file, setFile] = useState<string>();

    useEffect(() => {
        if (!withinFileEditor || isNewFile) {
            return;
        }

        if (withinFileEditor && params['*'] !== undefined && !isNewFile) {
            setFile(decodeURIComponent(params['*']).split('/').pop());
        }
    }, [withinFileEditor, isNewFile]);

    const breadcrumbs = (): { name: string; path?: string }[] => {
        if (directory === '.') {
            return [];
        }

        return directory
            .split('/')
            .filter(directory => !!directory)
            .map((directory, index, dirs) => {
                if (!withinFileEditor && index === dirs.length - 1) {
                    return { name: directory };
                }

                return { name: directory, path: `/${dirs.slice(0, index + 1).join('/')}` };
            });
    };

    return (
        <div css={tw`flex flex-grow-0 items-center text-sm text-theme-muted overflow-x-hidden`}>
            {renderLeft || <div css={tw`w-12`} />}/<span css={tw`px-1 text-theme-secondary`}>home</span>/
            <NavLink
                to={`/server/${id}/files`}
                css={tw`px-1 text-theme-secondary no-underline hover:text-theme-primary`}
            >
                container
            </NavLink>
            /
            {breadcrumbs().map((crumb, index) =>
                crumb.path ? (
                    <Fragment key={index}>
                        <NavLink
                            to={`/server/${id}/files#${encodePathSegments(crumb.path)}`}
                            css={tw`px-1 text-theme-secondary no-underline hover:text-theme-primary`}
                            end
                        >
                            {crumb.name}
                        </NavLink>
                        /
                    </Fragment>
                ) : (
                    <span key={index} css={tw`px-1 text-theme-secondary`}>
                        {crumb.name}
                    </span>
                ),
            )}
            {file && (
                <Fragment>
                    <span css={tw`px-1 text-theme-secondary`}>{file}</span>
                </Fragment>
            )}
        </div>
    );
};
