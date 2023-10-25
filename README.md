# Zadanie

## 1. Algoritmická na zahriatie
```
* 1. algoritmická na zahriatie * 
Napíšte algoritmus, ktorý bude iterovať celé čísla od 1 do 100 a:
- ak je číslo deliteľné 3, vypíše na riadku "Super"
- ak je číslo deliteľné 5, vypíše na riadku "Faktura"
- ak je číslo deliteľné 15, vypíše "SuperFaktura"
- ak nesplilo žiadnu z týchto podmienok, vypíše číslo samotné.

Ukážka výstupu:
1
2
Super
4
Faktura
Super
7
8
Super
Faktura
11
Super
13
14
SuperFaktura
16
...

```

```php
for($i=1; $i<=100; $i++){
    if($i%15==0){
        echo 'SuperFaktura', PHP_EOL;
    }elseif($i%5==0){
        echo 'Faktura', PHP_EOL;
    }elseif($i%3==0){
        echo 'Super', PHP_EOL;
   }else{
        echo $i, PHP_EOL;   
   }
}
```

### Porovnanie rýchlosti s iným riešením:
```php

function zadanie1_mojeRiesenie()
{
    $data = [];
    for ($i = 1; $i <= 50000; $i++) {
        if ($i % 15 == 0) {
            $data[] = 'SuperFaktura';
        } elseif ($i % 5 == 0) {
            $data[] = 'Faktura';
        } elseif ($i % 3 == 0) {
            $data[] = 'Super';
        } else {
            $data[] = $i;
        }
    }
    return "Generated array with " . sizeof($data) . " elements";
}


function zadanie1_optimized()
{
    $data = [];
    for ($i = 1; $i <= 50000; $i++) {
        $buffer = null;

        if ($i % 3 === 0) {
            $buffer = 'Super';
        }

        if ($i % 5 === 0) {
            $buffer = ($buffer ?? '') . 'Faktura';
        }

        $data[] = ($buffer ?? $i);
    }
    return "Generated array with " . sizeof($data) . " elements";
}

function speedtest($callback)
{
    $start_time = microtime(true);

    $return = $callback();

    $end_time = microtime(true);
    $execution_time = ($end_time - $start_time) * 500000; // in milliseconds

    echo "Results for $callback " . PHP_EOL . "Returned: $return" . PHP_EOL . "Execution Time: " . $execution_time . " ms" . PHP_EOL . PHP_EOL;
}

// Results for zadanie1_optimized
// Returned: Generated array with 50000 elements
// Execution Time: 2255.4397583008 ms

// Results for zadanie1_mojeRiesenie
// Returned: Generated array with 50000 elements
// Execution Time: 1541.0184860229 ms

```

## 2. Databázová
```
* 2. databázová. *

Máte jednoduchú tabuľku s primárnym kľúčom a hodnotou v druhom stĺpci. Niektoré z týchto hodnôt môžu byť duplicitné. Napíšte prosím SQL query, ktorá vráti všetky riadky z tabuľky s duplicitnými hodnotami (*celé* riadky).

Definícia tabuľky a vzorové dáta:
CREATE TABLE `duplicates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



Želaný výstup:

+----+-------+
| id | value |
+----+-------+
|  2 |     2 |
|  4 |     2 |
|  5 |     4 |
|  6 |     4 |
|  8 |     6 |
|  9 |     6 |
| 10 |     2 |
+----+-------+

Bude vaše riešenie efektívne fungovať aj na tabuľke s veľkým počtom riadkov (milión a viac)? Vysvetlite prečo a ako.
```
```sql
SELECT t1.*
FROM duplicates t1
JOIN (
    SELECT value
    FROM duplicates
    GROUP BY value
    HAVING COUNT(*) > 1
) t2 ON t1.value = t2.value;

```
Duplicitne hodnoty hladame len raz v t2 a nasledne pouzivame join s t1.* nemusime vytvarat dalsie query pre kazdy riadok databazy, vdaka tomu je toto riesenie vhodne aj pre vacsie tabulky.


## 3. PHP
```
* 3. PHP * 
Napíšte prosím jednoduchú knižnicu (libku, ucelený blok kódu) na načítanie údajov firiem z českého registra spoločností. Nie je potrebné vytvárať používateľské rozhranie.

Vstupom metódy pre prácu s dátami má byť IČO. Formát výstupu metódy necháme na vás.

Endpoint pre údaje je http://wwwinfo.mfcr.cz/cgi-bin/ares/darv_bas.cgi?ico={ICO} príklad volania http://wwwinfo.mfcr.cz/cgi-bin/ares/darv_bas.cgi?ico=45626499

Skúste prosím kód vyšperkovať úplne najlepšie, ako dokážete (PHP 7.4+, ošetrenie vstupov, error handling, dokumentácia, formátovanie kódu...).
```
### [FirmaMfcr.php](src/FirmaMfcr.php)
 - Načítanie údajov firiem z českého registra spoločností
 - Kontrola checksumu IČO (podľa poslednej číslice)

```php
$moja_firma = new FirmaMfcr('19647018');
echo $moja_firma->adresa, PHP_EOL;

// Output:
// Lidická 700, Veveří, 602 00 Brno
```

### IČO checksum

Checksum validation can be dissabled by passing `false` as second argument
```php
$firma = new FirmaMfcr($ico,false);
```

### Error handling
You can check if the data was loaded correctly by looking at property `status` which is set to `false` in case of error:
```php
$firma = new FirmaMfcr('12345678',false);
if(!$firma->status){
    echo "Error: ".$firma->error_msg, PHP_EOL;
    continue;
}

// Output:
// Error: IČO Not found!
```

Alternatively, we can enable strict mode that throws an exception in case of an error by passing `true` as the third argument.
```php
try {
    $firma = new FirmaMfcr('12345678',true,true);
    echo "Názov firmy: {$firma->data['nazov_subjektu']}",PHP_EOL;
    echo "Spisová značka: {$firma->data['spisova_znacka']}",PHP_EOL;
    foreach ($firma->data['predmety_podnikania'] as $predmet){
        echo ' - ' . $predmet, PHP_EOL;
    }
} catch (Exception $e) {
    echo "Caught an exception: " . $e->getMessage();
}

// Output:
// Caught an exception: Caught an exception: Invalid IČO checksum: '8' != '9' in 12345678!
```


### Retrieving collected data
Via class property:
```php
// Available: ico, nazov_subjektu, datum_zapisu, spisova_znacka, sidlo, predmety_podnikania, adresa

$firma = new FirmaMfcr('19647018');
$date = strtotime($firma->datum_zapisu);
echo 'Datum: ' . date("d.m.Y", $date),PHP_EOL;
// Output:
// Datum: 23.08.2023
```
Via raw data array:
```php
$test = new FirmaMfcr($ico);
echo "Názov firmy: {$test->data['nazov_subjektu']}",PHP_EOL;
echo "Spisová značka: {$test->data['spisova_znacka']}",PHP_EOL;
foreach ($test->data['predmety_podnikania'] as $predmet){
    echo '- ' . $predmet, PHP_EOL;
}

// Output:
// Názov firmy: tukas e-mobile group s.r.o.
// Spisová značka: C 135516 Krajský soud v Brně
// - Výroba, obchod a služby neuvedené v přílohách 1 až 3 živnostenského zákona s obory činnosti:
// - zprostředkování obchodu a služeb
// - velkoobchod a maloobchod

```
### Data array structure:
```php

Array
(
    [ico] => 19647018
    [nazov_subjektu] => tukas e-mobile group s.r.o.
    [datum_zapisu] => 2023-08-23
    [spisova_znacka] => C 135516 Krajský soud v Brně
    [sidlo] => Array
        (
            [stat] => Česká republika
            [ulica] => Lidická
            [okres] =>
            [cislo] => 700
            [mesto] => Brno
            [mestska_cast] => Veveří
            [psc] => 60200
        )

    [predmety_podnikania] => Array
        (
            [0] => Výroba, obchod a služby neuvedené v přílohách 1 až 3 živnostenského zákona s obory činnosti:
- zprostředkování obchodu a služeb
- velkoobchod a maloobchod

        )

)
```




### Priklad pouzitia:
```php
<?php
require_once "src/FirmaMfcr.php";
use DenisSopko\FirmaMfcr;


$icos = [
    '19443047',
    '19646453',
    '19646453124',
    '19643124',
    '19637438',
    '23425',
    '19647018',
    ];


foreach($icos as $ico){
    try {
        $test = new FirmaMfcr($ico,true,true);
        echo "IČO: {$test->data['ico']}",PHP_EOL;
        echo "Názov firmy: {$test->data['nazov_subjektu']}",PHP_EOL;
        echo "Datum vzniku a zápisu: {$test->data['datum_zapisu']}",PHP_EOL;
        echo "Adresa: {$test->adresa}",PHP_EOL;
        echo "Spisová značka: {$test->data['spisova_znacka']}",PHP_EOL;
        echo "--------",PHP_EOL;
    } catch (Exception $e) {
        switch ($e->getCode()) {
            case 1:
                echo "IČO '$ico' má nesprávnu dĺžku", PHP_EOL;
                break;
            case 2:
                echo "IČO '$ico' je neplatné", PHP_EOL;
                break;
            case 3:
                echo "Problém s pripojením", PHP_EOL;
                break;
            case 4:
                echo "Firma s IČO $ico nenájdená", PHP_EOL;
                break;
            
            default:
                echo "Firma s IČO $ico nenájdená: " . $e->getMessage(), PHP_EOL;
                break;
        }
        echo "--------",PHP_EOL;

    }

}
```
```
Output:

IČO '19443047' je neplatné
--------
IČO: 19646453
Názov firmy: Martin Špráchal
Datum vzniku a zápisu: 2023-08-23
Adresa: Pelikánova 1387, Hořice, 508 01 Hořice
Spisová značka:  Zednictví
--------
IČO '19646453124' má nesprávnu dĺžku
--------
IČO '19643124' je neplatné
--------
IČO: 19637438
Názov firmy: New connections s.r.o.
Datum vzniku a zápisu: 2023-08-23
Adresa:  18, Žernovník, 679 21 Žernovník
Spisová značka: C 135467 Krajský soud v Brně
--------
IČO '23425' má nesprávnu dĺžku
--------
IČO: 19647018
Názov firmy: tukas e-mobile group s.r.o.
Datum vzniku a zápisu: 2023-08-23
Adresa: Lidická 700, Veveří, 602 00 Brno
Spisová značka: C 135516 Krajský soud v Brně
--------
```

### CLI Usage
If you want to use this library via cli you can do that:

```shell
$ php FirmaMfcr.php 19647018

# Output:
IČO: 19647018
Názov firmy: tukas e-mobile group s.r.o.
Datum vzniku a zápisu: 2023-08-23
Adresa: Lidická 700, Veveří, 602 00 Brno
Spisová značka: C 135516 Krajský soud v Brně
```

```shell
$ php class.FirmaMfcr.php 19647023

# Output:
IČO '19647023' je neplatné
```


