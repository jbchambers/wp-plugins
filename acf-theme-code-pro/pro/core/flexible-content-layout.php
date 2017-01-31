<?php

// to do : make this a child class of field group class ?

// Class for a layout in a flexible content field.
class ACFTCP_Flexible_Content_Layout {

	// Properties
	private $layout_key;
	private $parent_field_id;

	public $name; // turn back to private?
	public $sub_fields; // turn back to private?

	/**
	 * $nesting_level
	 *
	 * 0 = not nested inside another field
	 * 1 = nested one level deep inside another field eg. repeater
	 * 2 = nested two levels deep inside other fields etc
	 */
	public $nesting_level;
	public $indent_count;
	public $field_location;

	// Constructor
	function __construct( $layout_key, $parent_field_id, $name, $nesting_level = 0, $indent_count = 0, $field_location ) {

		$this->layout_key = $layout_key;
		$this->parent_field_id = $parent_field_id;

		$this->name = $name;
		$this->sub_fields = $this->get_sub_fields();
		$this->nesting_level = $nesting_level;
		$this->indent_count = $indent_count;
		$this->field_location = $field_location;

	}

	// Get all sub fields in layout
	private function get_sub_fields() {

		// get all sub fields of parent field
		$query_args = array(
			'post_type' =>  array( 'acf-field' , 'acf' ), // to do : should this be a conditional?
			'post_parent' => $this->parent_field_id,
			'posts_per_page' => '-1',
			'orderby' => 'menu_order',
			'order' => 'ASC',
		);

		$fields_query = new WP_Query( $query_args );
		$all_sub_fields = $fields_query->posts;

		// get only fields that belong to layout
		$layout_sub_fields = array();

		foreach ( $all_sub_fields as $sub_field ) {

			$sub_field_content = unserialize( $sub_field->post_content );
			$sub_field_layout_key = $sub_field_content['parent_layout'];

			// if sub field belongs to layout, add it to the array of fields
			if ( $this->layout_key == $sub_field_layout_key ) {
				array_push( $layout_sub_fields, $sub_field );
			}

		}

		return $layout_sub_fields;

	}

	// Renders theme PHP for layout sub fields
	public function render_sub_fields() {

		// loop through sub fields
		foreach ( $this->sub_fields as $sub_field ) {

			// temp to resolve php notice
			$field_location = '';

			$acftc_field = new ACFTCP_Field( $this->nesting_level, $this->indent_count, $field_location, $sub_field );

			$acftc_field->render_field();

		}

	}

}
