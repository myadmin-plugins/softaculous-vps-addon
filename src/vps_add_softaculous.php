<?php
/**
 * VPS Functionality
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2018
 * @package MyAdmin
 * @category VPS
 */

/**
 * Adds Softaculous to a VPS
 * @return void
 */
function vps_add_softaculous() {
	function_requirements('class.AddServiceAddon');
	$addon = new AddServiceAddon();
	$addon->load(__FUNCTION__, 'Softaculous', 'vps', VPS_SOFTACULOUS_COST);
	$addon->process();
}
