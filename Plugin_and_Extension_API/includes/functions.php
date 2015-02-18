<?php
/**
 * common functions collection
 * 
 * @author	Björn Bartels <info@dragon-projects.de>
 * @package CONTENIDO Plugin and Extension API
 * 
 */

/**
 * peaInclude: shortcut to contenido_include to load/include PEA php-files.
 *
 * @see Contenido:contenido_include
 *
 * @param $where string The area which should be included
 * @param $what string The filename of the include
 * @param $force boolean If true, force the file to be included
 *
 * @return none
 *
 */
function peaInclude ($where, $what, $force = false)
{
	global $cfg;
	$where = $cfg['path']['contenido'] . $cfg['path']['includes'] . "pea/" . $where. "/" . $what;
	contenido_include($where, $what, $force);
}

/**
 * convertBytes: convert a shorthand byte value from a PHP configuration directive to an integer value
 * 
 * @param    string   $value
 * 
 * @return   int
 */
function convertBytes( $value ) {
    if ( is_numeric( $value ) ) {
        return $value;
    } else {
        $value_length = strlen( $value );
        $qty = substr( $value, 0, $value_length - 1 );
        $unit = strtolower( substr( $value, $value_length - 1 ) );
        switch ( $unit ) {
            case 'k': $qty *= 1024; break;
            case 'm': $qty *= (1024*1024); break;
            case 'g': $qty *= (1024*1024*1024); break;
        }
        return $qty;
    }
}

