#!/usr/bin/env python3
"""Prepare generated PNG landing illustrations for web without SVG/stock-image substitutions."""
from __future__ import annotations
from pathlib import Path
import argparse
from PIL import Image, ImageOps

SPECS = {
    "hero": (1200, 1500),
    "problem-lunch": (1200, 900),
    "problem-organizer": (1200, 900),
    "problem-tastes": (1200, 900),
    "before": (1200, 900),
    "after": (1200, 900),
    "inside-shelves": (1200, 900),
    "terms-dialog": (1200, 800),
    "lead-relax": (1200, 800),
}
LIMIT = 250_000


def convert(source: Path, target: Path, dimensions: tuple[int, int]) -> None:
    if not source.is_file():
        raise FileNotFoundError(f"Missing generated image: {source}")
    image = Image.open(source)
    image = ImageOps.exif_transpose(image).convert("RGB")
    width, height = dimensions
    if image.width < width or image.height < height:
        raise ValueError(f"{source}: {image.size}, requires at least {dimensions}")
    image = ImageOps.fit(image, dimensions, method=Image.Resampling.LANCZOS, centering=(0.5, 0.5))
    target.parent.mkdir(parents=True, exist_ok=True)
    for quality in (89, 83, 77, 71, 65, 58, 51, 43, 35):
        image.save(target, "WEBP", quality=quality, method=6)
        if target.stat().st_size <= LIMIT:
            print(f"{target}: {width}x{height}, {target.stat().st_size} bytes, quality={quality}")
            return
    target.unlink(missing_ok=True)
    raise ValueError(f"{source}: cannot compress under {LIMIT} bytes at specified dimensions")


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--originals", type=Path, default=Path("design/originals"))
    parser.add_argument("--output", type=Path, default=Path("images"))
    args = parser.parse_args()
    missing = [str(args.originals / (name + ".png")) for name in SPECS if not (args.originals / (name + ".png")).is_file()]
    if missing:
        parser.error("Missing original AI-generated PNG images:\n" + "\n".join(missing))
    for name, dims in SPECS.items():
        convert(args.originals / (name + ".png"), args.output / (name + ".webp"), dims)


if __name__ == "__main__":
    main()
