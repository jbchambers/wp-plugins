<?php
// Font Awesome Icon Field (from .org repo)

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// save format
$save_format = isset( $this->settings['save_format'] ) ? $this->settings['save_format'] : '';

// This is used to return the font awesome icon as a variable
if ( $save_format == 'element' ) {
	echo $this->indent . htmlspecialchars("<?php " . $this->the_field_method . "( '" . $this->name ."' ); ?>")."\n";
}

if ( $save_format == 'class' ) {
	echo $this->indent . htmlspecialchars("<i class=\"fa <?php " . $this->the_field_method . "( '" . $this->name ."' ); ?>\"></i>"). "\n";
}

if ( $save_format == 'unicode'  || $save_format == 'object' ) {
	echo $this->indent . htmlspecialchars("<?php \$".$this->var_name." = " . $this->get_field_method . "( '" . $this->name ."' );")."\n";
	echo $this->indent . htmlspecialchars("// var_dump( \$".$this->var_name." ); ?>")."\n";
}
