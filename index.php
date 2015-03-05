<?php

header('Content-type: text/html; charset=utf-8');
error_reporting(E_ALL);

$ini_string='
[игрушка мягкая мишка белый]
цена = '.  mt_rand(1, 10).';
количество заказано = '.  mt_rand(1, 10).';
осталось на складе = '.  mt_rand(0, 10).';
diskont = diskont'.  mt_rand(0, 2).';
    
[одежда детская куртка синяя синтепон]
цена = '.  mt_rand(1, 10).';
количество заказано = '.  mt_rand(1, 10).';
осталось на складе = '.  mt_rand(0, 10).';
diskont = diskont'.  mt_rand(0, 2).';
    
[игрушка детская велосипед]
цена = '.  mt_rand(1, 10).';
количество заказано = '.  mt_rand(1, 10).';
осталось на складе = '.  mt_rand(0, 10).';
diskont = diskont'.  mt_rand(0, 2).';
      
';
$bd=  parse_ini_string($ini_string, true);
//($bd);

//1. Надо разобрать корзину в отдельный массив.
//$names = array();

function parseBasket ($arr){
    foreach ($arr as $i) {
        global $names;
        $names = array_keys($arr);
    }
}
parseBasket($bd);
//print_r($names);

//2. Сделать функцию. вычисления итогового кол-ва товара,
//если товара на складе не хватает.
$deficiency = array_flip($names);           //готовим булевый массив, показывающий недостаток товара на складе
foreach ($deficiency as $value) {           //нужен для вывода указаний о недостатке в графе "уведомления"
    $deficiency[$names[$value]] = null;
}

function correctAmmount($name,$bd){
    global $deficiency;
    if ($bd[$name]['количество заказано'] <= $bd[$name]['осталось на складе']) {
        return $bd[$name]['количество заказано'];
    }else{
        $deficiency[$name] = 1;
        return $bd[$name]['осталось на складе'];
    }
}

//3.Функции вычисления скидок (2 вида).

// сначала написал ф-ю через swich, потом решил написать более универсальную
//function saleDefault($name,$bd) {           //возвращает скидку на товар по-умолчанию
//    switch ($bd[$name]['diskont']) {
//        case 'diskont1':
//            return 0.1;
//            break;
//        case 'diskont2':
//            return 0.2;
//            break;
//        default:
//            return 0;
//            break;
//    }
//}
function saleDefault($name,$bd) {
    return substr($bd[$name]['diskont'], 7, 1) / 10;
}

function saleSpecial($name, $bd) {              //возвращает скидку на велосипед
    if (correctAmmount($name, $bd) >= 3){
        return 0.3;
    } else {
        return saleDefault ($name, $bd);
    }
}

function priceWithSale($name,$bd) {         //возвращает цену со скидкой
    if ($name == 'игрушка детская велосипед'){
        return $bd[$name]['цена']*(1 - saleForBicycle($bd));
    }else{
        return $bd[$name]['цена']*(1 - saleDefault($name, $bd));
    }
}

//4.Вывести корзину в виде таблицы: Наименование|Цена за ед.|Кол-во|Скидка на товар|Остаток на складе|Стоимость с учетом наличия|Стоимость со скидкой|

$basket = array();                          //формирую массив корзины
for ($i = 0; $i < count($names); $i++) {
    if ($bd[$names[$i]]['количество заказано'] > 0){
        $basket['Наименование товара'][$i] = $names[$i];
        $basket['Цена за ед.'][$i] = $bd[$names[$i]]['цена'];
        $basket['Кол-во'][$i] = $bd[$names[$i]]['количество заказано'];
        if ($names[$i] == 'игрушка детская велосипед'){
            $basket['Скидка на товар'][$i] = saleSpecial($names[$i],$bd)*100 . '%';
        }else{
            $basket['Скидка на товар'][$i] = saleDefault($names[$i], $bd)*100 . '%';
        }
        $basket['Остаток на складе'][$i] = $bd[$names[$i]]['осталось на складе'];
        $basket['Стоимость с учетом наличия'][$i] = correctAmmount($names[$i], $bd)*$basket['Цена за ед.'][$i];
        $basket['Стоимость со скидкой'][$i] =  $basket['Стоимость с учетом наличия'][$i]*(100 - $basket['Скидка на товар'][$i])/100;
        
    }
}
//print_r($basket);

//5.Секция ИТОГО (тоже таблица):

//готовлю массив $summery для выводов результатов в графе ИТОГО
$summery['Кол-во наименований в корзине'] = 0;
$summery['Кол-во товаров'] = 0;
$summery['Сумма заказа'] = 0;

for ($i = 0; $i < count($basket['Наименование товара']); $i++) {
    if (correctAmmount($basket['Наименование товара'][$i], $bd) > 0){
        $summery['Кол-во наименований в корзине']++;
    }
    $summery['Кол-во товаров'] += correctAmmount($basket['Наименование товара'][$i], $bd);
    $summery['Сумма заказа'] += $basket['Стоимость со скидкой'][$i];
}
//print_r($summery);
//print_r($deficiency);


// ВЫВОД
echo "<h2>КОРЗИНА</h2>";

if (!$basket == NULL) {
    // Вывод корзины
    echo "<table width = 100%>";
    echo "<tr>";
    foreach ($basket as $key => $value) {
        echo "<th bgcolor='silver'>$key</th>";
    }
    echo "</tr>";
    for ($i = 0; $i < count($names); $i++){
        echo "<tr align = 'center'>";
        foreach ($basket as $key => $value) {
            echo "<td>$value[$i]</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
      
    // Вывод Уведомления
    if (!array_sum($deficiency) == null) {
        echo "<h3>Уведомления:</h3>";
        echo "<br>В данный момент на складе отсутствует необходимое количество следующего товара: ";
        foreach ($deficiency as $key => $value) {
            if (!$value == 0) {
                echo "|".$key."|".' ';
            }
        }
        echo '.';
        echo "<br>Итоговая стоимость пересчитана с учетом количества товара на складе. Приносим свои извинения.";
    }
    
   // Вывод Скидки
    if (correctAmmount('игрушка детская велосипед', $bd) >= 3) {
        echo "<h3>Скидки:</h3>";
        echo "<br>За покупку 'игрушка детская велосипед' в количестве трех либо более штук Вы получаете скидку 30% на этот товар!";
    }

    //Вывод ИТОГО
    echo "<h3>ИТОГО:</h3>";
    echo "<table width = 100%>";
    echo "<tr>";
    foreach ($summery as $key => $value) {
        echo "<th bgcolor='silver'>$key</th>";
    }
    echo "</tr>";
    echo "<tr align = 'center'>";
    foreach ($summery as $key => $value) {
        echo "<td>$value</td>";
    }
    echo "</tr>";
    echo "</table>";
    
    // К оплате
    echo "<h3>СУМММА К ОПЛАТЕ: ".$summery['Сумма заказа']."</h3>";
}else{
    echo '<br>В вашей корзине пока нет товаров.';
}



