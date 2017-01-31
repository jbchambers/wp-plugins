<?php
// Clone field

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

$cloned_items = $this->settings['clone']; // get cloned field(s) or field group(s)
$cloned_item_type = ''; // initialise

if ( !empty( $cloned_items) ) { // make sure at least one field has been selected to be cloned

    foreach ($cloned_items as $cloned_item => $cloned_item_slug) {

        $cloned_item_type = substr( $cloned_item_slug, 0, 5);

        // Cloned field
        if ( 'field' === $cloned_item_type ) {

            // Get cloned field

            // Old code:
            // global $wpdb;
            // $single_field_object = $wpdb->get_row( "SELECT * FROM $wpdb->posts WHERE post_name = '$cloned_item_slug' AND post_type = 'acf-field'" );

            $cloned_field_query = new WP_Query( array( 'name' => $cloned_item_slug,
            'post_type' => 'acf-field' ));
            $single_field_object = $cloned_field_query->post;

            if ( $single_field_object ) {

                $field_location = ''; // TODO: Temp - incomplete location functionality

                $acftc_field = new ACFTCP_Field( $this->nesting_level, $this->indent_count, $field_location, $single_field_object, $this); // Last argument is $clone_parent_acftcp_group_ref

                $acftc_field->render_field();

            }

        }
        // Cloned field group
        elseif ( 'group' === $cloned_item_type ) {

            // Get cloned field group froms posts table
            $cloned_field_group_post_object = get_page_by_path( $cloned_item_slug, 'OBJECT', 'acf-field-group' );

            if ( $cloned_field_group_post_object ) {

                $field_group_location = ''; // TODO: Temp - incomplete location functionality

                $cloned_acftcp_group = new ACFTCP_Group( $cloned_field_group_post_object->ID, $this->nesting_level, $this->indent_count, $field_group_location, $this );

                $cloned_acftcp_group->render_field_group();

            }

        }

        $cloned_item_type = ''; // reset

    }

} else { // no fields selected inside clone field

    echo $this->indent . htmlspecialchars("<?php // No fields selected inside clone field ?>")."\n";

}
