<?php
// Flexible content field

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// field location
$field_location = '';

// set sub field nesting level and indent
$sub_field_indent_count = $this->indent_count + ACFTCP_Core::$indent_flexible_content;

// don't need to check for no layouts, acf ui insists on at least one
echo $this->indent . htmlspecialchars("<?php if ( have_rows( '".$this->name."' ) ): ?>")."\n";
echo $this->indent . htmlspecialchars("	<?php while ( have_rows( '".$this->name."' ) ) : the_row(); ?>")."\n";

$layout_count = 0;

// loop through layouts
foreach ( $this->settings['layouts'] as $layout ) {

	// create layout object that contains layout sub fields
	$acftc_layout = new ACFTCP_Flexible_Content_Layout( $layout['key'], $this->id, $layout['name'], $this->nesting_level + 1, $sub_field_indent_count, $field_location);

	//  if layout has sub fields
	if ( !empty( $acftc_layout->sub_fields ) ) {

		// if first non empty layout
		if ( 0 == $layout_count ) {
			// render 'if'
			echo $this->indent . htmlspecialchars("		<?php if ( get_row_layout() == '" . $acftc_layout->name . "' ) : ?>")."\n";
		} else {
			// render 'elseif'
			echo $this->indent . htmlspecialchars("		<?php elseif ( get_row_layout() == '" . $acftc_layout->name . "' ) : ?>")."\n";
		}

		$acftc_layout->render_sub_fields();

		$layout_count++;
	}
	// layout has no sub fields
	else {
		echo $this->indent . htmlspecialchars("		<?php // warning: layout '" . $acftc_layout->name . "' has no sub fields ?>")."\n"; // to do : use Label instead of Name?
	}

}

echo $this->indent . htmlspecialchars("		<?php endif; ?>")."\n";
echo $this->indent . htmlspecialchars("	<?php endwhile; ?>")."\n";
echo $this->indent . htmlspecialchars("<?php else: ?>")."\n";
echo $this->indent . htmlspecialchars("	<?php // no layouts found ?>")."\n";
echo $this->indent . htmlspecialchars("<?php endif; ?>")."\n";
