<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class for field group functionality
 */
class ACFTCP_Group {

	// field group id
	private $id;

	/**
	 * array of all fields in field group
	 *
	 * if using postmeta table this will be an array of post meta objects
	 * if using posts table this will be and array of Post objects
	 */
	public $fields;

	/**
	 * nesting level
	 *
	 * 0 = not nested inside another field
	 * 1 = nested one level deep inside another field eg. repeater
	 * 2 = nested two levels deep inside other fields etc
	 */
	public $nesting_level;

	// theme code indent for the field group
	public $indent_count;

	// if the field group is a clone
	public $clone = false;
	public $clone_parent_acftcp_group_ref = null;

	// field location (for options panel etc)
	public $location; // TODO: Incomplete functionality

	/**
	 * Constructor for field group
	 *
	 * @param $field_group_id					int
	 * @param $nesting_level					int
	 * @param $indent_count						int
	 * @param $location							string
	 * @param $clone_parent_acftcp_group_ref	object ref
	 */
	function __construct( $field_group_id, $nesting_level = 0, $indent_count = 0, $location = '', &$clone_parent_acftcp_group_ref = null ) {

		if ( !empty( $field_group_id ) ) {

			$this->id = $field_group_id;
			$this->fields = $this->get_fields();
			$this->nesting_level = $nesting_level;
			$this->indent_count = $indent_count;
			$this->location = $location; // TODO: Incomplete functionality
			$this->clone_parent_acftcp_group_ref = &$clone_parent_acftcp_group_ref;

		}

	}


	/**
	* Get all the fields in the field group.
	*
	* @return array of all fields (post objects) in the field group
	*/
	private function get_fields() {

		if ( 'postmeta' == ACFTCP_Core::$db_table ) { // ACF
			return $this->get_fields_from_postmeta_table();
		 } elseif ( 'posts' == ACFTCP_Core::$db_table ) { // ACF PRO
			return $this->get_fields_from_posts_table();
		}

	}


	/**
	* Get fields from postmeta table
	*
	* @return array of all fields (post meta objects) in the field group
	*/
	private function get_fields_from_postmeta_table() {

		global $wpdb;

		// get table prefix
		$postmeta_table_name = $wpdb->prefix . 'postmeta';

		// query postmeta table for fields in this field group
		$fields = $wpdb->get_results( "SELECT * FROM " . $postmeta_table_name . " WHERE post_id = " . $this->id . " AND meta_key LIKE 'field_%'" );

		return $fields;

	}


	/**
	* Get fields from posts table
	*
	* @return array of all fields (post objects) in the field group
	*/
	private function get_fields_from_posts_table() {

		// wp query args for all ACF fields for this field group
		$query_args = array(
			'post_type' => 'acf-field',
			'post_parent' => $this->id,
			'posts_per_page' => '-1',
			'orderby' => 'menu_order',
			'order' => 'ASC',
		);

		$fields_query = new WP_Query( $query_args );

		return $fields_query->posts;

	}


	/**
	 * Render theme PHP for all fields in field group
	 */
	public function render_field_group() {

		// ACF - create, sort and render fields
		if ( 'postmeta' == ACFTCP_Core::$db_table ) {

			// create an array of ACFTCP_Field objects
			$acftc_fields = array();

			foreach ( $this->fields as $field ) {

				$acftc_field = new ACFTCP_Field(	$this->nesting_level,
												$this->indent_count,
												$this->location,
												$field
												);

				array_push( $acftc_fields, $acftc_field );

			}

			// sort fields
			usort( $acftc_fields, array( $this, "compare_field_order") );

			// render fields
			foreach ( $acftc_fields as $acftc_field ) {
				$acftc_field->render_field();
			}

		 }

		// ACF PRO - create and render fields (no sorting required)
		elseif ( 'posts' == ACFTCP_Core::$db_table ) {

			// create and render ACFTCP_Field objects
			foreach ( $this->fields as $field_post_obj ) {

				$acftc_field = new ACFTCP_Field(	$this->nesting_level,
												$this->indent_count,
												$this->location, // TODO: Incomplete location functionality
												$field_post_obj,
												$this->clone_parent_acftcp_group_ref // TODO: Add this clone bit to the postmeta table func above?
												);

				$acftc_field->render_field();

			}

		}

	}

	/**
	 * Field order number comparion, used by usort() in render_field_group()
	 */
	private function compare_field_order( $a, $b ) {

		return $a->settings['order_no'] > $b->settings['order_no'];

	}

}
