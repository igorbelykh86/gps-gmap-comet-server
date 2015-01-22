<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Geo;

/**
 * Description of MapUnit
 *
 * @author igor
 */
class MapUnit implements IMapUnit {
    
    protected $latlon = NULL;
    
    public function __construct($latitude, $longitude = NULL) {
        if(empty($longitude) && strpos($latitude, ',') === FALSE) {
            throw new \UnexpectedValueException("An unexpected coordinate format.");
        }
        if(empty($longitude)) {
            list($lat, $lon) = explode(',', $latitude);
            $this->latlon = new MapLatLon($lat, $lon);
        } else {
            $this->latlon = new MapLatLon($latitude, $longitude);
        }
    }
    
    /**
     * 
     * @return MapLatLon returns object of the point on the map
     */
    public function getLatLon() {
        return $this->latlon;
    }
    
}
