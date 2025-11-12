declare module '*.jpg';
declare module '*.png';
declare module '*.svg';
declare module '*.svg?raw' {
    const content: string;
    export default content;
}
declare module '*.css';
