import json
import random
import os

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
CARS_FILE = os.path.join(BASE_DIR, 'cars.json')
OUT_FILE  = os.path.join(BASE_DIR, 'descriptions.json')


def describe(car):

    name = car['name']
    power = car['power']
    engine = car['engine']
    km = car['km']
    year = car['year']
    car_type = car['type']
    fuel = car['fuel']

    intros = {
        'Купе': [
            f"{name} — это спорткар, созданный для скорости.",
            f"{name} — воплощение динамики и адреналина.",
            f"{name} — купе для тех, кто ценит драйв.",
        ],
        'Кроссовер': [
            f"{name} — просторный кроссовер для всей семьи.",
            f"{name} — комфорт и мощь в одном автомобиле.",
            f"{name} — идеален для города и трассы.",
        ],
        'Седан': [
            f"{name} — элегантный седан бизнес-класса.",
            f"{name} — сочетание комфорта и технологий.",
            f"{name} — представительский седан для деловых людей.",
        ],
    }
    intro = random.choice(intros.get(car_type, [f"{name} — отличный выбор."]))

    if power >= 500:
        power_text = f"Под капотом — {engine} объёмом и {power} л.с. Это настоящий зверь: разгон до 100 км/ч займёт считанные секунды."
    elif power >= 350:
        power_text = f"Двигатель {engine} выдаёт {power} л.с. — мощности хватит и для города, и для трассы."
    else:
        power_text = f"Двигатель {engine} мощностью {power} л.с. обеспечивает уверенный ход в любых условиях."

    if km < 10000:
        km_text = f"Пробег всего {km:,} км — автомобиль практически новый.".replace(',', ' ')
    elif km < 30000:
        km_text = f"Пробег {km:,} км — в отличном состоянии, обслужен.".replace(',', ' ')
    else:
        km_text = f"Пробег {km:,} км — проверен по всем регламентам.".replace(',', ' ')

    fuel_texts = {
        'Бензин': "Классический бензиновый мотор — надёжность и привычная динамика.",
        'Гибрид': "Гибридная силовая установка — экономия топлива без потери мощности.",
        'Дизель': "Дизельный мотор — тяга и экономичность на дальних дистанциях.",
        'Электро': "Полностью электрический привод — тишина и мгновенный отклик.",
    }
    fuel_text = fuel_texts.get(fuel, "")

    endings = [
        f"{year} год, идеальное состояние — готов к новому владельцу.",
        f"Автомобиль {year} года — полностью проверен и готов к передаче.",
        f"Год выпуска — {year}. Вложений не требует, обслужен.",
        "Приезжайте на тест-драйв — почувствуйте сами.",
        "Проверен по VIN, история прозрачна, документы в порядке.",
    ]
    ending = random.choice(endings)

    parts = [intro, power_text, km_text, fuel_text, ending]
    return " ".join(p for p in parts if p).strip()


def main():
    if not os.path.exists(CARS_FILE):
        print(f"❌ Файл {CARS_FILE} не найден")
        return

    with open(CARS_FILE, 'r', encoding='utf-8') as f:
        cars = json.load(f)

    descriptions = {}
    for car in cars:
        descriptions[str(car['id'])] = describe(car)

    with open(OUT_FILE, 'w', encoding='utf-8') as f:
        json.dump(descriptions, f, ensure_ascii=False, indent=2)

    print(f"✅ Сгенерировано описаний: {len(descriptions)}")
    print(f"📄 Файл: {OUT_FILE}")


if __name__ == '__main__':
    main()