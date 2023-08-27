<?php

/*
 * FirmaMfcr.php
 * Načítanie údajov firiem z českého registra spoločností
 * 
 * 
 * Error Messages and Status Codes:
 *  
 * Invalid IČO length
 *   This error occurs when the provided IČO has an incorrect length.
 *   Status Code: 1
 *
 * Invalid IČO checksum
 *   This error occurs when the provided IČO fails the checksum validation.
 *   Status Code: 2
 *
 * No data from mfcr.cz
 *   This error occurs when there's no data available from the mfcr.cz source.
 *   It could be due to connection issues or unavailability of the data.
 *   Status Code: 3
 *
 * IČO Not found
 *   This error occurs when the provided IČO does not correspond to any registered entity.
 *   Status Code: 4
 *
 * These error codes and messages help to identify and communicate specific issues that might arise
 * while working with IČO data retrieval. Developers can use these codes to handle different error
 * scenarios appropriately.
 * 
 * Error codes are stored in $error_code property while messages are in $error_msg
 * 
 * 
 * 
 * Created by: Denis Sopko
 * Email: d.sopko11@gmail.com
 * GitHub: https://github.com/mohger
 * GitLab: https://gitlab.com/Mohg
 * Project: Zadanie 3 pre superfaktura.sk
 */

namespace DenisSopko;
class FirmaMfcr{
    public $ico;
    public $do_checksum;
    public $xml_data; // raw xml from mfcr
    public $raw_data; // array 
    public $data = [];
    public $status = false;
    public $error_msg = null;
    public $error_code = 0;
    public $strict = false;

    //data array access
    public $nazov_subjektu,$datum_zapisu,$spisova_znacka,$sidlo,$predmety_podnikania,$adresa;

    public function __construct($ico,$do_checksum = true, $strict = false) {
        try {
            $this->do_checksum = $do_checksum; // Test with checksum, default true
            $this->strict = $strict; // If true throw exception in case of failure
            $this->ico = $this->ico_check($ico);
            $this->read_data();
            $this->status = true;

        } catch (\Exception $e) {
            $this->status = false;
            $this->error_msg = $e->getMessage();
            if($this->strict) throw new \Exception("Caught an exception: " . $e->getMessage(),$e->getCode());
        }
    }


    // clean any unwanted characters and check validity via checksum (last digit of IČO)
    private function ico_check($input){

        $ico_checked = preg_replace('/\D/','',$input);

        if($this->do_checksum){
            //test length
            if(strlen($ico_checked) != 8 && strlen($ico_checked) != 6){
                $this->error_code = 1;
                throw new \Exception("Invalid IČO length: '$ico_checked' = " . strlen($ico_checked). ". Expected 8!". PHP_EOL, $this->error_code);
            }
    
    
            //test checksum
            $checksum = 0;
            for ($i=0; $i < strlen($ico_checked) -1; $i++) { 
                $weight = 8-$i;
                $checksum += $ico_checked[$i] * $weight;
            }
            $kontrolne_cislo = $ico_checked[$i++];
            $checksum = (11-($checksum%11))%10;

            if($kontrolne_cislo != $checksum){
                $this->error_code = 2;
                throw new \Exception("Invalid IČO checksum: '$kontrolne_cislo' != '$checksum' in $ico_checked!". PHP_EOL, $this->error_code);
            }

        }


        return $ico_checked;
    }

    
    public function adresa_string(){
        //adress string e.g. Habrová 812, Nehvizdy, 250 81 Nehvizdy
        $data = $this->data;
        $address = $data["sidlo"]["ulica"] .' '. $data["sidlo"]["cislo"] . ', ';
        if ($data["sidlo"]["mestska_cast"]) $address .=  $data["sidlo"]["mestska_cast"]. ', ' ;
        $address .= substr($data["sidlo"]["psc"], 0, 3) . ' '. substr($data["sidlo"]["psc"], 3);
        $address .= ' '. $data["sidlo"]["mesto"];
        return $address;
    }
    
    private  function try_domvalue($dom,$val){
        $element = $dom->getElementsByTagName($val)->item(0);
        if($element) return $element->nodeValue;
        return null;
    }

    // get raw xml from mfcr
    private function read_data(){
        $url = 'http://wwwinfo.mfcr.cz/cgi-bin/ares/darv_bas.cgi?ico='.$this->ico;
        //if more data  use http://wwwinfo.mfcr.cz/cgi-bin/ares/darv_or.cgi?ico= and/or http://wwwinfo.mfcr.cz/cgi-bin/ares/darv_vr.cgi?ico=

        $this->xml_data = file_get_contents($url);
        if(!$this->xml_data){
            $this->error_code = 3;
            throw new \Exception("No data from mfcr.cz!". PHP_EOL, $this->error_code);
        }

        //parse xml into $this->data
        $dom = new \DOMDocument();
        $dom->loadXML($this->xml_data);

        if($this->try_domvalue($dom, 'EK') == 1){
            $this->error_code = 4;
            throw new \Exception("IČO Not found!". PHP_EOL, $this->error_code);
        }
        
        $data = [];
        $data["ico"] = $this->try_domvalue($dom, 'ICO');
        $data["nazov_subjektu"] = $this->try_domvalue($dom, 'OF');
        $data["datum_zapisu"] = $this->try_domvalue($dom, 'DV');
        
        $data["spisova_znacka"] = $this->try_domvalue($dom, 'OV');
        $data["spisova_znacka"] .= " ". $this->try_domvalue($dom, 'T');
        
        $data["sidlo"]["stat"] = $this->try_domvalue($dom, 'NS');
        $data["sidlo"]["ulica"] = $this->try_domvalue($dom, 'NU');
        $data["sidlo"]["okres"] = $this->try_domvalue($dom, 'NOK');
        $data["sidlo"]["cislo"] = $this->try_domvalue($dom, 'CD');
        $data["sidlo"]["mesto"] = $this->try_domvalue($dom, 'N');
        $data["sidlo"]["mestska_cast"] = $this->try_domvalue($dom, 'NCO');
        $data["sidlo"]["psc"] = $this->try_domvalue($dom, 'PSC');

        

        // predmety_podnikania with cleanup
        if($dom->getElementsByTagName('PP')->item(0)){
            foreach ($dom->getElementsByTagName('PP')->item(0)->childNodes as $item){
                // dont include empty lines
                $cleanedText = preg_replace('/^\h*\v+/m', '', $item->nodeValue);
                if ($cleanedText) $data["predmety_podnikania"][] = $cleanedText;
            }
        }

        $this->data = $data; //save

        $this->nazov_subjektu = $data["nazov_subjektu"];
        $this->datum_zapisu = $data["datum_zapisu"];
        $this->spisova_znacka = $data["spisova_znacka"];
        $this->sidlo = $this->adresa_string();
        $this->predmety_podnikania = $data["predmety_podnikania"];
        $this->adresa = $this->adresa_string();

    }
}




// Code to handle command-line usage
if (basename(__FILE__) === basename($_SERVER["PHP_SELF"])) {
        
    // defaults
    $ico = null;
    $do_checksum = true;
    $strict = true;

    // First arg should be ICO and is required
    if (isset($argv[1])) {
        $ico = $argv[1];
    } else {
        echo "IČO required: use 'php FirmaMfcr.php 12345678'",PHP_EOL;
        exit(1); //bad length (0)
    }


    try {
        $test = new FirmaMfcr($ico,$do_checksum,$strict);
        echo "IČO: {$test->data['ico']}",PHP_EOL;
        echo "Názov firmy: {$test->data['nazov_subjektu']}",PHP_EOL;
        echo "Datum vzniku a zápisu: {$test->data['datum_zapisu']}",PHP_EOL;
        echo "Adresa: {$test->adresa}",PHP_EOL;
        echo "Spisová značka: {$test->data['spisova_znacka']}",PHP_EOL;
    } catch (\Exception $e) {
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
        exit($e->getCode());
    }
}