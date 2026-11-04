# Issue #1 v3 — графика для «Еда в точку»

## Статус исполнения

- ✅ Новый адаптивный макет под девять отдельных сцен (при наличии оригиналов PNG).
- ✅ Исходники старых SVG удалены из папки `images/` вместе с устаревшим workflow.
- ✅ Инструмент преобразования PNG в WebP: `python tools/convert_illustrations.py`.
- ⛔ Новые 9 картинок не готовы: генератор возвращает макеты **всего лендинга**, а не изображения сцен; альтернативный сервис генерации завершился технической ошибкой. Ни один из этих результатов не выдан за готовую картинку.
- ⛔ До появления 9 изображений текущие `images/*.webp` являются *старыми* иллюстрациями v2; блок «Было/стало» временно берёт две области прежней совмещённой картинки.

## Промпт-основа для отдельной генерации каждого PNG

Generate **one single standalone illustration**, NOT a website screenshot, graphic design layout, collage, poster or advertisement with text.

Modern vivid 2.5D CGI service illustration with saturated colors, strong contrast, realistic soft shadows, warm white or light gray office setting, clear expressive faces, natural proportional hands and five-finger poses, fully visible bodies and necks. Consistent recurring characters: **Anna** — curly shoulder-length chestnut hair, black blazer, white blouse; **Oleg** — short dark hair, beard and glasses, navy blue suit; **Maria** — red hair, coral blouse; **Igor** — short dark hair, pale-blue shirt. Green #16a34a, navy #14213d and warm yellow-orange #f59e0b as accents. Smart food refrigerator: tall dark frame, glass door, warm LED strips, **exactly five distinct shelves** of fresh salads, hot meals, sandwiches and rolls, pastries and desserts, chilled bottled drinks. No writing, no labels, no letters, no digits, no brand names, no watermark. All relevant people and the refrigerator must be within the central 85% of the frame. Solid full-frame interior background.

**Generate each scene independently as a separate PNG**, keeping appearances and lighting identical across scenes:

1. `hero.png` — **4:5, 1200×1500**. Anna smiles and welcomes viewers, a bowl of green salad in one hand and her other hand naturally gestures at a tall fully visible glass-front fridge occupying 55% of the frame.
2. `problem-lunch.png` — **4:3, 1200×900**. Oleg and Igor in coats walk OUT of the office glass doors into the street looking at a restaurant with an unlettered fork icon in the distance; large wall clock and daylight.
3. `problem-organizer.png` — **4:3, 1200×900**. Anna fully visible sitting at a desk with papers and a laptop, stressed and exhausted, hands against the temples, a thought cloud with a plate/clock/checklist ICONS only.
4. `problem-tastes.png` — **4:3, 1200×900**. Maria, Oleg, Igor sit around the SAME table and have genuinely DIFFERENT dishes: a salad, a burger and a steaming hot meal in a container. One smiling, one hesitant, one happily eating.
5. `before.png` — **4:3, 1200×900**. Igor waiting in a cafe queue with several other full-body people, he visibly checks his watch, tired and impatient. No signage or text.
6. `after.png` — **4:3, 1200×900**. The SAME Igor happily takes a fresh meal from the smart refrigerator in the office, coworkers working and eating in the background.
7. `inside-shelves.png` — **4:3, 1200×900**. Large closeup of an OPEN fridge, exactly five clear distinct shelves: salad; hot prepared meals and soup; sandwiches and rolls; baked goods and desserts; cold beverages. Each food item recognizably different and appetizing, not duplicated icons.
8. `terms-dialog.png` — **3:2, 1200×800**. Oleg demonstrates a free spot on the wall to Anna, natural two-handed measuring gesture of roughly one meter, electric outlet clearly visible and a taped floor outline.
9. `lead-relax.png` — **3:2, 1200×800**. Anna reclines in an office chair with hands behind her head, smiling peacefully, a glass smart fridge and two coworkers eating nearby, everything in frame.

## Сборка и проверка

1. Сохранить полученные **оригиналы генерации** в `design/originals/` под именами выше.
2. Выполнить `python -m pip install pillow` и `python tools/convert_illustrations.py`. Скрипт проверит наличие всех 9 PNG и сохранит WebP не более 250 KB в `images/`.
3. Проверить сюжеты, пять полок, людей, руки, отсутствие надписей; визуальную проверку нейросети скрипт не заменяет.
4. В `index.html` заменить временные два кадра из `before-after.webp` на `before.webp` и `after.webp`, размеры 1200×900, после чего удалить `images/before-after.webp` и в `.github/workflows/image-audit.yml` заменить `before-after` (1600×900) на `before` и `after` (1200×900) в списке `expected`.
5. Запустить CI и браузерные проверки на 1440, 768, 390 px; приложить скриншоты в PR.
6. Мержить только после выполнения всех критериев Issue #1 v3.

**Внимание:** имеющиеся старые картинки не соответствуют художественным критериям v3. Автоматическая проверка размера и загрузки не означает художественную приёмку.
