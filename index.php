<?php

header('Content-type: text/html; charset=utf-8');
error_reporting(E_ALL);

//товары в корзине
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
//print_r($bd);

//Список специальных скидок
$ini_action='
[игрушка мягкая мишка белый]
amount = 6;
diskont = 50;

[игрушка детская велосипед]
amount = 3;
diskont = 30;

';
$action = parse_ini_string($ini_action, TRUE);
//print_r($action);


//1. Надо разобрать корзину в отдельный массив.

$names = array_keys($bd);
//print_r($names);

//2. Сделать функцию. вычисления итогового кол-ва товара,
//если товара на складе не хватает.
$deficiency = array();           //готовим булевый массив, показывающий недостаток товара на складе
foreach ($names as $value) {           //нужен для вывода указаний о недостатке в графе "уведомления"
    $deficiency[$value] = null;
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
$arr_saleSpecial = array();                     // массив для памяти специальных скидок

function saleDefault($name,$bd) {
    return substr($bd[$name]['diskont'], 7, 1) / 10;
}

function saleSpecial($name, $bd) {              //возвращает скидку на велосипед
    global $arr_saleSpecial;
    global $action;
    if (correctAmmount($name, $bd) >= $action[$name]['amount']){
        $arr_saleSpecial[] = $name;
        return $action[$name]['diskont']/100;
    } else {
        return saleDefault ($name, $bd);
    }
}

//4.Вывести корзину в виде таблицы: Наименование|Цена за ед.|Кол-во|Наличие на складе|Скидка на товар|Цена со скидкой|Стоимость с учетом наличия|Стоимость со скидкой|

$basket = array();                          //формирую массив корзины
for ($i = 0; $i < count($names); $i++) {
    if ($bd[$names[$i]]['количество заказано'] > 0){
        $basket['Наименование товара'][$i] = $names[$i];
        $basket['Цена за ед.'][$i] = $bd[$names[$i]]['цена'];
        $basket['Кол-во'][$i] = $bd[$names[$i]]['количество заказано'];
        $basket['Наличие на складе'][$i] = correctAmmount($names[$i], $bd); 
        
        // для специальных скидок
        if (array_key_exists($names[$i], $action)){
            $basket['Скидка на товар'][$i] = saleSpecial($names[$i],$bd)*100 . '%';
        }else{
            $basket['Скидка на товар'][$i] = saleDefault($names[$i], $bd)*100 . '%';
        }
        
        $basket['Цена со скидкой'][$i] = $basket['Цена за ед.'][$i]*((100-$basket['Скидка на товар'][$i])/100);
        $basket['Стоимость с учетом наличия'][$i] = $basket['Наличие на складе'][$i]*$basket['Цена за ед.'][$i];
        $basket['Стоимость со скидкой'][$i] =  $basket['Стоимость с учетом наличия'][$i]*(100 - $basket['Скидка на товар'][$i])/100;
    }
}
//print_r($basket);

//5.Секция ИТОГО (тоже таблица):

//готовлю массив $summery для выводов результатов в графе ИТОГО
$summery['Кол-во наименований в корзине'] = count($names);
$summery['Кол-во товаров'] = array_sum($basket['Наличие на складе']);
$summery['Сумма заказа'] = array_sum($basket['Стоимость с учетом наличия']);
$summery['Ваша скидка'] = $summery['Сумма заказа'] - array_sum($basket['Стоимость со скидкой']);

//print_r($summery);
//print_r($deficiency);

// ВЫВОД
echo "<h2>КОРЗИНА</h2>";

if ($basket) {
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
    //echo var_dump($deficiency);
      
    // Вывод Уведомления
    if (array_sum($deficiency)) {
        
        echo "<h3>Уведомления:</h3>";
        echo "<br>В данный момент на складе отсутствует необходимое количество следующего товара: ";
        foreach ($deficiency as $key => $value) {
            if ($value) {
                echo "'".$key."'".' ';
            }
        }
        echo '.';
        echo "<br>Итоговая стоимость пересчитана с учетом количества товара на складе. Приносим свои извинения.";
    }
   
   // Вывод Скидки
   if ($arr_saleSpecial) {
        echo "<h3>Скидки:</h3>";
        foreach ($arr_saleSpecial as $name) {
            echo "<br>За покупку '$name' в количестве ".$action[$name]['amount'].
                    " либо более штук Вы получаете скидку ".$action[$name]['diskont']."% на этот товар!";
        }
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
    echo "<h3>СУМММА К ОПЛАТЕ: ".array_sum($basket['Стоимость со скидкой'])."</h3>";
}else{
    echo '<br>В вашей корзине пока нет товаров.';
}



