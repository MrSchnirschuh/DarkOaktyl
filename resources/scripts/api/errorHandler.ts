import { httpErrorToHuman } from './http';

export const handleApiError = (error: any, flashMessage?: (msg: string) => void): string => {
    const message = httpErrorToHuman(error);
    if (flashMessage) flashMessage(message);
    return message;
};

export const handleApiErrorAsync = async (
    error: any, 
    flashMessage?: (msg: string) => void
): Promise<string> => {
    return handleApiError(error, flashMessage);
};