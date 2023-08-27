<?php
namespace Tests;
require_once 'vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use DenisSopko\FirmaMfcr;

class FirmaMfcrTest extends TestCase {
    public function test_ico_lenght_ok_passes() {
        $firmaMfcr = new FirmaMfcr('19647018');

        $this->assertEquals(true, $firmaMfcr->status);
        $this->assertEquals('19647018', $firmaMfcr->ico);
    }

    public function test_ico_bad_lenght_doesnt_pass() {
        $firmaMfcr = new FirmaMfcr('19647018123');

        $this->assertNotEquals('19647018123', $firmaMfcr->ico);

        $this->assertEquals(false, $firmaMfcr->status);
        //check error code
        $this->assertEquals('1', $firmaMfcr->error_code);
    }

    public function test_ico_invalid_passes_while_ignoring_checksum() {
        $firmaMfcr = new FirmaMfcr('19647018123',do_checksum: false);

        $this->assertEquals('19647018123', $firmaMfcr->ico);
        $this->assertNotEquals('1', $firmaMfcr->error_code);
    }

    public function test_strict_mode_throws_exception_with_invalid_ico() {
        $this->expectException(\Exception::class);
        $firmaMfcr = new FirmaMfcr('19647018123',do_checksum: true,strict: true);

        $this->assertEquals(false, $firmaMfcr->status);
        $this->assertEquals('19647018123', $firmaMfcr->ico);
    }

    public function test_ico_checksum_doesnt_pass_with_invalid_ico() {
        $firmaMfcr = new FirmaMfcr('19547018');

        $this->assertNotEquals('19547018', $firmaMfcr->ico);

        $this->assertEquals(false, $firmaMfcr->status);
        //check error code
        $this->assertEquals('2', $firmaMfcr->error_code);
    }

    public function test_nonexisting_ico_fails() {
        $firmaMfcr = new FirmaMfcr('11111119');

        $this->assertEquals(false, $firmaMfcr->status);
        //check error code
        $this->assertEquals('4', $firmaMfcr->error_code);
    }

    public function test_known_ico_for_address() {
        $firmaMfcr = new FirmaMfcr('19647018');

        $this->assertEquals(true, $firmaMfcr->status);
        $this->assertStringContainsString('Lidická', $firmaMfcr->adresa);
        $this->assertStringContainsString('700', $firmaMfcr->adresa);
        $this->assertStringContainsString('Veveří', $firmaMfcr->adresa);
        //check error code
        $this->assertEquals(0, $firmaMfcr->error_code);
    }

    public function test_raw_data_vs_property() {
        $firmaMfcr = new FirmaMfcr('19647018');

        $this->assertEquals(true, $firmaMfcr->status);
        $this->assertEquals($firmaMfcr->data['ico'], $firmaMfcr->ico);
        $this->assertEquals($firmaMfcr->data['nazov_subjektu'], $firmaMfcr->nazov_subjektu);
        $this->assertEquals($firmaMfcr->data['datum_zapisu'], $firmaMfcr->datum_zapisu);

        //check error code
        $this->assertEquals(0, $firmaMfcr->error_code);
    }


    // Removed due to potential failures
    // public function test_random_valid_icos_this_can_fail_if_unlucky(){
    //     $numberOfIcos = 25;
    //     $icos = $this->randomValidIco($numberOfIcos);
        
    //     $failures = 0;
    //     foreach($icos as $ico){
    //         $firmaMfcr = new FirmaMfcr($ico);
    //         if(!$firmaMfcr->status){
    //             $failures++;
    //         }
    //     }
    //     //we can expect at least some of the icos are valid
    //     $this->assertNotEquals($numberOfIcos, $failures);
    // }
   


    private function randomValidIco($num = 1){
        $icos = [];
        for ($i=0; $i < $num; $i++) { 
            $ico = '';
            $checksum = 0;
            for ($j=0; $j < 7; $j++) {
                $number = rand(0,9);
                $weight = 8-$j;
                $checksum += $number * $weight;
                $ico .= $number;
            }
            $ico .= (11-($checksum%11))%10;
            $icos[]=$ico;
        }
        if($num === 1) return $ico;
        return $icos;
    }

   

}