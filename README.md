# Zadanie

## 1. Algoritmická na zahriatie
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

## 2. Databázová
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


