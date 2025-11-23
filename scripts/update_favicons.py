"""Utility for regenerating favicon/icon assets from the DarkOak logo."""
from __future__ import annotations

from io import BytesIO
from pathlib import Path

from PIL import Image, ImageOps

try:
    from cairosvg import svg2png
except (ImportError, OSError):  # pragma: no cover - optional dependency when svg rasterization unavailable
    svg2png = None

ROOT = Path(__file__).resolve().parents[1]
BRAND_DIR = ROOT / "public" / "assets" / "brand"
FAVICON_DIR = ROOT / "public" / "favicons"
COLOR_SOURCE_PNG = BRAND_DIR / "DarkOak_CL.png"
COLOR_SOURCE_SVG = BRAND_DIR / "DarkOak_CL.svg"
MONO_SOURCE = BRAND_DIR / "DarkOak_BW.svg"

PNG_TARGETS: dict[str, int] = {
    # Android Chrome assets
    "android-chrome-192x192.png": 192,
    "android-chrome-512x512.png": 512,
    "android-icon-36x36.png": 36,
    "android-icon-48x48.png": 48,
    "android-icon-72x72.png": 72,
    "android-icon-96x96.png": 96,
    "android-icon-144x144.png": 144,
    "android-icon-192x192.png": 192,
    # Apple touch icons
    "apple-icon-57x57.png": 57,
    "apple-icon-60x60.png": 60,
    "apple-icon-72x72.png": 72,
    "apple-icon-76x76.png": 76,
    "apple-icon-114x114.png": 114,
    "apple-icon-120x120.png": 120,
    "apple-icon-144x144.png": 144,
    "apple-icon-152x152.png": 152,
    "apple-icon-180x180.png": 180,
    "apple-icon.png": 180,
    "apple-icon-precomposed.png": 180,
    "apple-touch-icon.png": 180,
    # Generic favicons
    "favicon-16x16.png": 16,
    "favicon-32x32.png": 32,
    "favicon-96x96.png": 96,
    # Microsoft tiles
    "ms-icon-70x70.png": 70,
    "ms-icon-144x144.png": 144,
    "ms-icon-150x150.png": 150,
    "ms-icon-310x310.png": 310,
    "mstile-150x150.png": 150,
}

ICO_SIZES = (16, 32, 48)

def _center_resized(image: Image.Image, size: int) -> Image.Image:
    """Resize while keeping aspect ratio, then center on a transparent square canvas."""
    resized = ImageOps.contain(image, (size, size), method=Image.Resampling.LANCZOS)
    canvas = Image.new("RGBA", (size, size), (0, 0, 0, 0))
    offset = ((size - resized.width) // 2, (size - resized.height) // 2)
    canvas.paste(resized, offset, resized)
    return canvas

def generate_pngs(source: Image.Image) -> None:
    for filename, size in PNG_TARGETS.items():
        destination = FAVICON_DIR / filename
        destination.parent.mkdir(parents=True, exist_ok=True)
        image = _center_resized(source, size)
        image.save(destination, format="PNG")
        print(f"Wrote {destination.relative_to(ROOT)}")

def generate_ico(source: Image.Image) -> None:
    destination = FAVICON_DIR / "favicon.ico"
    icons = [_center_resized(source, size) for size in ICO_SIZES]
    icons[0].save(destination, format="ICO", sizes=[(size, size) for size in ICO_SIZES])
    print(f"Wrote {destination.relative_to(ROOT)}")

def copy_mono_svg() -> None:
    destination = FAVICON_DIR / "safari-pinned-tab.svg"
    destination.write_bytes(MONO_SOURCE.read_bytes())
    print(f"Copied {destination.relative_to(ROOT)}")

def load_color_source() -> Image.Image:
    svg_available = COLOR_SOURCE_SVG.exists() and svg2png is not None
    if svg_available:
        png_bytes = svg2png(url=str(COLOR_SOURCE_SVG), output_width=1024, output_height=1024)
        return Image.open(BytesIO(png_bytes)).convert("RGBA")

    if COLOR_SOURCE_PNG.exists():
        return Image.open(COLOR_SOURCE_PNG).convert("RGBA")

    raise SystemExit(
        "Missing DarkOak color logo. Provide either DarkOak_CL.svg (preferred) or DarkOak_CL.png in public/assets/brand."
    )

def main() -> None:
    if not MONO_SOURCE.exists():
        raise SystemExit(f"Missing monochrome logo source at {MONO_SOURCE}")

    with load_color_source() as source:
        generate_pngs(source)
        generate_ico(source)
    copy_mono_svg()


if __name__ == "__main__":
    main()
