#!/usr/bin/env python3
"""Prepare actual AI-generated photo PNG files for v4 landing (no SVG or mockup crops)."""
from __future__ import annotations
from pathlib import Path
from PIL import Image, ImageOps
import argparse

SPECS: dict[str, tuple[int, int]] = {
    "hero": (1200, 1500),
    "fridge-open": (1200, 900),
    "food-hot": (800, 600),
    "food-salads": (800, 600),
    "food-soups": (800, 600),
    "food-snacks": (800, 600),
    "food-breakfast": (800, 600),
    "food-desserts": (800, 600),
    "food-drinks": (800, 600),
    "place-office": (1200, 900),
    "place-production": (1200, 900),
    "place-business-center": (1200, 900),
    "place-clinic": (1200, 900),
    "before": (1200, 900),
    "after": (1200, 900),
    "lead-relax": (1200, 800),
}
MAX_BYTES = 250_000


def convert(source: Path, destination: Path, target_size: tuple[int, int]) -> None:
    if not source.is_file():
        raise FileNotFoundError(f"Generated PNG source missing: {source}")
    with Image.open(source) as original:
        if original.format != "PNG":
            raise ValueError(f"Expected a genuine PNG file: {source}")
        image = ImageOps.exif_transpose(original).convert("RGB")
    # ChatGPT отдаёт максимум 1024x1536 / 1536x1024: допускаем небольшой апскейл (до 20%).
    if image.width < target_size[0] * 0.8 or image.height < target_size[1] * 0.8:
        raise ValueError(f"{source}: resolution {image.size} is too small for {target_size}")
    # Preserve central subjects: a small crop is acceptable only when the original
    # aspect ratio differs; the photo composition itself must have center safe space.
    image = ImageOps.fit(image, target_size, method=Image.Resampling.LANCZOS, centering=(0.5, 0.5))
    destination.parent.mkdir(parents=True, exist_ok=True)
    for quality in (90, 84, 78, 72, 65, 57, 50, 42, 34):
        image.save(destination, format="WEBP", quality=quality, method=6)
        if destination.stat().st_size <= MAX_BYTES:
            print(f"{destination} {image.width}x{image.height} {destination.stat().st_size} bytes (quality {quality})")
            return
    destination.unlink(missing_ok=True)
    raise ValueError(f"Unable to fit {source} in {MAX_BYTES} bytes without severe compression")


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--originals", type=Path, default=Path("design/originals"))
    parser.add_argument("--output", type=Path, default=Path("images"))
    parser.add_argument("--check-only", action="store_true", help="Only check that all source images exist and are PNG")
    args = parser.parse_args()
    missing = [args.originals / (name + ".png") for name in SPECS if not (args.originals / (name + ".png")).is_file()]
    if missing:
        parser.error("Missing AI-generated original images:\n" + "\n".join(map(str, missing)))
    for name, resolution in SPECS.items():
        source = args.originals / (name + ".png")
        with Image.open(source) as im:
            if im.format != "PNG":
                parser.error(f"Not a PNG: {source}")
            print(f"Original verified: {source} {im.size}")
        if not args.check_only:
            convert(source, args.output / (name + ".webp"), resolution)


if __name__ == "__main__":
    main()
