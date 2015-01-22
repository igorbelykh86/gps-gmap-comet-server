<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Geo;

/**
 * Description of IMapUnit
 *
 * @author igor
 */
interface IMapUnit {
    /**
     * @return MapLatLon
     */
    public function getLatLon();
}
