<?php
defined('ABSPATH') or die("No script kiddies please!");
/**
 * Customizer Demo Importer Setting class
 * 
 *
 * 
 */

final class CDI_Customizer_Setting extends WP_Customize_Setting {

	/**
	 * Import an option value for this setting.
	 *
	 * @param mixed $value The value to update.
	 */
	public function import( $value ) {
		$this->update( $value );
	}
}
