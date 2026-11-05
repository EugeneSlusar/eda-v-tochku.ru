# Исходники фотосцен для дизайна v4

Эта папка предназначена **только для настоящих отдельных PNG-файлов, сгенерированных нейросетью по сюжету**. Здесь не должны появляться скриншоты целого сайта, нарезка из референса, SVG или стоковые материалы.

Нужно 16 файлов:
`hero.png`, `fridge-open.png`, `food-hot.png`, `food-salads.png`, `food-soups.png`, `food-snacks.png`, `food-breakfast.png`, `food-desserts.png`, `food-drinks.png`, `place-office.png`, `place-production.png`, `place-business-center.png`, `place-clinic.png`, `before.png`, `after.png`, `lead-relax.png`.

Сюжеты: `design/STYLE_V4_ORANGE.md`; готовые промпты для ChatGPT: `design/CHATGPT_PROMPTS.md`. Размер PNG — как отдаёт ChatGPT (1536×1024 / 1024×1536), конвертер обрежет по центру. Конвертация: `pip install pillow && python tools/convert_illustrations.py`.

**Требования:** без текста, логотипов, вывесок и узнаваемых брендов; общая тёплая фотостилистика. Исходники в `design/originals/` не деплоятся. Результаты `images/*.webp` — максимум 250 КБ каждый.
