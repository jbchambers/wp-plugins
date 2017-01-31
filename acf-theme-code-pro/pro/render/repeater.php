<?php
// Repeater field

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Repater field vars
$field_location = '';
$nesting_arg = 0;

// Set sub field nesting level and indent
$sub_field_indent_count = $this->indent_count + ACFTCP_Core::$indent_repeater;
$repeater_field_group = new ACFTCP_Group( $this->id, $nesting_arg + 1, $sub_field_indent_count, $field_location );

// As long as the repeater has some fields
if ( !empty( $repeater_field_group->fields ) ) {

	echo $this->indent . htmlspecialchars("<?php if ( have_rows( '" . $this->name ."' ) ) : ?>")."\n";
	echo $this->indent . htmlspecialchars("	<?php while ( have_rows( '" . $this->name. "' ) ) : the_row(); ?>")."\n";

	$repeater_field_group->render_field_group();

	echo $this->indent . htmlspecialchars("	<?php endwhile; ?>")."\n";
	echo $this->indent . htmlspecialchars("<?php else : ?>")."\n";
	echo $this->indent . htmlspecialchars("	<?php // no rows found ?>")."\n";
	echo $this->indent . htmlspecialchars("<?php endif; ?>")."\n";

}
