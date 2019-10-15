<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Configuration options for Xero private application
 */

$config = array(
	'consumer'	=> array(
    	'key'		=> 'V5VZOMISBSP0PWGEKRR4ADZYUE0MO6',
    	'secret'	=> 'Q6ZRKOFEUK3HUICXQQA7R8KXP50GF1'
    ),
    'certs'		=> array(
    	'private'  	=> APPPATH.'certs/privatekey.pem',
    	'public'  	=> APPPATH.'certs/publickey.cer'
    ),
    'format'    => 'json'
);