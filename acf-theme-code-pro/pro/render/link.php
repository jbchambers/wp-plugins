<?php
// Link field
// https://wordpress.org/plugins/acf-link/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Basic support for the link field
echo $this->indent . htmlspecialchars("<?php \$$this->name = " . $this->get_field_method . "( '" . $this->name ."' ); ?>")."\n";
echo $this->indent . htmlspecialchars("<a href=\"<?php echo \$".$this->var_name."['url']; ?>\"><?php echo \$".$this->var_name."['title']; ?></a>")."\n";
