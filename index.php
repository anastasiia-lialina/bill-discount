<?php

//Разделитель между товарами
const PRODUCT_SEPARATOR = PHP_EOL;

//типы скидок
const DISCOUNT_TYPE_PERCENT = 'percent';
const DISCOUNT_TYPE_MONEY = 'money';

//Количество знаков после запятой в цене
const PRICE_PRECISION = 2;

const PRICE_PATTERN = '/(?<=\s)(\d+)(?=\₽)/';

/**
 * парсим сумму товара из строки
 */
function parcePrice(string $bill): array
{
    preg_match_all(PRICE_PATTERN, $bill, $matches, PREG_SET_ORDER);

    return $matches;
}

/**
 * Проверяем тип скидки
 */
function checkDiscountType(string $type): bool
{
    return $type === DISCOUNT_TYPE_PERCENT || $type === DISCOUNT_TYPE_MONEY;
}

/**
 * Вычисляем какой процент составляет скидка в рублях от суммы всего чека
 */
function calculateDiscount(string $bill, float $discount): float
{
    $prices = parcePrice($bill);
    $totalPrice = array_sum(array_column($prices, 0));

    return $discount / $totalPrice * 100;
}

/**
 * @param float $price
 * @param float $discount
 * @return float
 */
function calculateNewPrice(float $price, float $discount): float
{
    return $price - ($price / 100 * $discount);
}

/**
 * Получаем строку товара с новой ценой
 */
function getNewProductLine(string $product, float $discount): string
{
    $price = parcePrice($product)[0][0] ?? null;
    //Если в чеке есть строка без цены (например комментарий какой нибудь), то оставляем её без изменений
    if ($price === null) {
        return $product . PRODUCT_SEPARATOR;
    }

    $newPrice = calculateNewPrice($price, $discount);
    $newPriceRounded = round($newPrice, PRICE_PRECISION);
    $newProductLine = preg_replace (PRICE_PATTERN, $newPriceRounded, $product);

    return $newProductLine . PRODUCT_SEPARATOR;
}

function validate(float $discount, string $discountType, string $bill): ?string
{
    if ($bill === '') {
        return 'Отсутствует чек';
    }

    if (checkDiscountType($discountType) === false) {
        return 'Неизвестный тип скидки';
    }

    if ($discount < 0) {
        return 'Размер скидки должен быть >= 0';
    }

    return null;
}

/**
 * Возвращает товары со скидками
 * В идеале создать класс с конструктором, в котором присвоить скидку, тип скидки и чек в свойства (чтобы не надо было передавать их в аргументы функций)
 */
function getDiscount(float $discount, string $discountType, string $bill): string
{
    if (($error = validate($discount, $discountType, $bill)) !== null) {
        return $error;
    }

    // Если скидка равна нулю, то не нужно делать никаких расчётов
    if ($discount == 0) {
        return $bill;
    }

    if ($discountType === DISCOUNT_TYPE_MONEY) {
        $discount = calculateDiscount($bill, $discount);
    }

    $products = explode(PRODUCT_SEPARATOR, $bill);

    $result = '';

    foreach ($products as $product) {
        $result .= getNewProductLine($product, $discount);
    }

    return $result;
}

//Примеры
var_dump(getDiscount(100, 'money',
    "Кроссовки: 2000₽,
    Шорты: 1000₽,
    Футболка: 500₽."
));

var_dump(getDiscount(50, 'percent',
    "Шорты 1шт. 1000₽
    Платье 1шт. 1000₽
    Юбка 1шт. 1000₽"
));