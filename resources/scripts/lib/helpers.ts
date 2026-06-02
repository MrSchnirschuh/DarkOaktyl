/**
 * Given a valid six character HEX color code, converts it into its associated
 * RGBA value with a user controllable alpha channel.
 */
function hexToRgba(hex: string, alpha = 1): string {
    // noinspection RegExpSimplifiable
    if (!/#?([a-fA-F0-9]{2}){3}/.test(hex)) {
        return hex;
    }

    // noinspection RegExpSimplifiable
    const match = hex.match(/[a-fA-F0-9]{2}/g);
    if (!match) return hex;
    const [r, g, b] = match.map(v => parseInt(v, 16));

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

export { hexToRgba };
