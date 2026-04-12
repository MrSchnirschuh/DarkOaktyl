import { httpErrorToHuman } from './http';

/**
 * Centralized error handler for API calls.
 * Converts errors to human-readable messages and optionally displays them via flash messages.
 *
 * @param error - The error from an API call
 * @param flashMessage - Optional function to display error messages
 * @returns Human-readable error message
 */
export const handleApiError = (error: any, flashMessage?: (msg: string) => void): string => {
    const message = httpErrorToHuman(error);
    if (flashMessage) {
        flashMessage(message);
    }
    return message;
};

/**
 * Wrapper for async functions to provide consistent error handling.
 * Use this for async/await patterns.
 *
 * @param promise - The promise to wrap
 * @param flashMessage - Optional function to display error messages
 * @returns Result of the promise
 * @throws The original error after handling
 */
export const withErrorHandling = async <T>(
    promise: Promise<T>,
    flashMessage?: (msg: string) => void
): Promise<T> => {
    try {
        return await promise;
    } catch (error) {
        handleApiError(error, flashMessage);
        throw error;
    }
};

/**
 * Creates a standard promise wrapper for API calls.
 * This reduces the boilerplate of `.then().catch()` patterns.
 */
export const createApiPromise = <T, Args extends any[]>(
    fn: (...args: Args) => Promise<T>
): ((...args: Args) => Promise<T>) => {
    return (...args) =>
        new Promise((resolve, reject) => {
            fn(...args)
                .then(resolve)
                .catch(reject);
        });
};
