<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace MyServer;

use Geo\GPRMCUnit as GPRMCUnit;

/**
 * Description of UpdateItem
 *
 * @author igor
 */
class UpdateItem {
    private $unit_id;
    private $gprmc_unit;
    
    public function __construct(GPRMCUnit $unit, $unit_id) {
        if((int) $unit_id < 1) {
            throw new \UnexpectedValueException("\$unit_id must be an integer value more than 0");
        }
        $this->gprmc_unit = $unit;
        $this->unit_id = (int) $unit_id;
    }
    
    public function getID() {
        return $this->unit_id;
    }
    
    public function getGPRMCUnit() {
        return $this->gprmc_unit;
    }
    
    public function getTimestamp() {
        return $this->gprmc_unit->getTimestamp();
    }
    
    public function updateGRPMCData(GPRMCUnit $unit) {
        if($unit->getTimestamp() > $this->getTimestamp()) {
            $this->gprmc_unit = $unit;
        }
    }
}
