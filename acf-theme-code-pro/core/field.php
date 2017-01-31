<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class for field functionality
 */
class ACFTCP_Field {

	private $render_partial;

	private $nesting_level;
	private $indent_count;
	private $indent = '';

	private $quick_link_id = '';
	private $the_field_method = 'the_field';
	private $get_field_method = 'get_field';
	private $get_field_object_method = 'get_field_object';

	private $id = null; // only used if posts table and required for flexible layouts
	private $label;
	private $name;
	private $type;
	private $var_name;

	/**
	 * All unserialized field data to be used in partials for edge cases.
	 * Needs to be public to sort fields.
	 */
	public $settings;

	private $clone = false;

	private $location;


	/**
	 * Constructor
	 *
	 * @param $nesting_level					int
	 * @param $indent_count						int
	 * @param $location							string
	 * @param $field_data_obj					object
	 * @param $clone_parent_acftcp_field_ref	object ref
	 */
	function __construct( $nesting_level = 0, $indent_count = 0 , $location = '', $field_data_obj = null, &$clone_parent_acftcp_field_ref = null ) {

		$this->nesting_level = $nesting_level;
		$this->indent_count = $indent_count;

		// TODO: Incomplete location functionality
		$this->location = $location;

		// if location set to options page, add the options parameter
		if ($this->location == 'options_page') {
			$this->location = '\', \'option';

		// else set location to an empty string
		} else {
			$this->location = '';
		}

		// if field is nested
		if ( 0 < $this->nesting_level ) {

			// calc indent string
			$this->indent = $this->get_indent();

			// use ACF sub field methods instead
			$this->the_field_method = 'the_sub_field';
			$this->get_field_method = 'get_sub_field';
			$this->get_field_object_method = 'get_sub_field_object';

		}

		if ( "postmeta" == ACFTCP_Core::$db_table ) {
			$this->construct_from_postmeta_table( $field_data_obj );
		} elseif ( "posts" == ACFTCP_Core::$db_table ) {
			$this->construct_from_posts_table( $field_data_obj );
		}

		// cloned fields
		if ( $clone_parent_acftcp_field_ref ) {

			$this->clone = true;
			$clone_settings = $clone_parent_acftcp_field_ref->settings;

			if ( 1 === $clone_settings['prefix_name'] ) {
				$this->name = $clone_parent_acftcp_field_ref->name . '_' . $this->name;
			}

		}

		// partial to use for rendering
		$this->render_partial = $this->get_render_partial();

	}

	// Set field properties using data from postmeta table
	private function construct_from_postmeta_table( $field_data_obj ) {

		if ( !empty( $field_data_obj ) ) {

			// unserialize meta values
			$this->settings = unserialize( $field_data_obj->meta_value );

			// to do : note absence of ID property here
			$this->label = $this->settings['label'];
			$this->name = $this->settings['name'];
			$this->type = $this->settings['type'];
			$this->var_name = $this->get_var_name();

			// if field is not nested
			if ( 0 == $this->nesting_level ) {

				// get quick link id
				$this->quick_link_id = $this->settings['key'];

			}

		}

	}


	// Set field properties using data from posts table
	private function construct_from_posts_table( $field_data_obj ) {

		if ( !empty( $field_data_obj ) ) {

			// unserialize content
			$this->settings = unserialize( $field_data_obj->post_content );

			$this->id = $field_data_obj->ID; // required for flexible layout
			$this->label = $field_data_obj->post_title;
			$this->name = $field_data_obj->post_excerpt;
			$this->var_name = $this->get_var_name();
			$this->type = $this->settings['type'];

			// if field is not nested
			if ( 0 == $this->nesting_level ) {

				// get quick link id
				$this->quick_link_id = $this->id;

			}

		}

	}


	// Get indent string for nested fields
	private function get_indent() {

		$indent = '';

		for ( $i = $this->indent_count; $i > 0 ; $i-- ) {
			$indent .= '	';
		}

		return $indent;

	}

	// Get the variable name
	private function get_var_name() {

		// Replace any hyphens with underscores
		$var_name = str_replace('-', '_', $this->name);

		// Replace any other special chars with underscores
		$var_name = preg_replace('/[^A-Za-z0-9\-]/', '_', $var_name);

		// Replace multiple underscores with single
		$var_name = preg_replace('/_+/', '_', $var_name);

		return $var_name;

	}


	// Get the path to the partial used for rendering the field
	private function get_render_partial() {

		if ( !empty( $this->type ) ) {

			// Field types only supported in TC Pro
			if ( file_exists( ACFTCP_Core::$plugin_path . 'pro' ) &&
				 in_array( $this->type, ACFTCP_Core::$tc_pro_field_types ) ) {
				$render_partial = ACFTCP_Core::$plugin_path . 'pro/render/' . $this->type . '.php';
			}
			
			// Basic field types with a shared partial
			elseif ( in_array( $this->type, ACFTCP_Core::$basic_types ) ) {
				$render_partial = ACFTCP_Core::$plugin_path . 'render/basic.php';
			}

			// Field types with their own partial
			else {
				$render_partial = ACFTCP_Core::$plugin_path . 'render/' . $this->type . '.php';
			}

			return $render_partial;

		}

	}

	// Render theme PHP for field
	public function render_field() {

		if ( !empty($this->type) ) {

			// Bail early for these field types
			if ($this->type == 'tab' ||
			 	$this->type == 'message' ||
				$this->type == 'accordion' ||
				$this->type == 'enhanced_message' ||
				$this->type == 'row') {

				return;
			}

			if ( 0 == $this->nesting_level && !$this->clone ) {

				// Open field meta div
				echo '<div class="acftc-field-meta">';

					// Setup a var for debug mode
					$debug_mode = '';

					// Chcek if debug mode is set, escape and store the value
					if ( isset( $_GET["debug"] ) ) {

						$debug_mode = htmlspecialchars( $_GET["debug"] );

					}

					// If debug mode is true
					if( $debug_mode == 'on' ) {

						// Echo the label as a heading for debugging
						echo htmlspecialchars('<h2>Debug: '. $this->label .'</h2>');

					} else {

						// Echo the code block title as pseudo contnet to avoid selection
						echo '<span class="acftc-field-meta__title" data-type="'. $this->type .'"data-pseudo-content="'. $this->label .'"></span>';

					}

				// Close field meta div
				echo '</div>';

				// Open div for field code wrapper (used for the button etc)
				echo '<div class="acftc-field-code" id="acftc-' . $this->quick_link_id . '">';

				// Copy button
				echo '<a href="#" class="acftc-field__copy" title="Copy to Clipboard"></a>';

				// PHP code block for field
				echo '<pre class="line-numbers"><code class="language-php">';

			}

			// Include field type partial
			if ( file_exists( $this->render_partial ) ) {
				include( $this->render_partial );
			}

			// Field not supported at all (yet)
			else {
				echo $this->indent . htmlspecialchars( "<?php // The " . $this->type  . " field type is not supported in this verison of the plugin. ?>" ) . "\n";
				echo $this->indent . htmlspecialchars( "<?php // Contact http://www.hookturn.io to request support for this field type. ?>" ) . "\n";
			}

			if ( 0 == $this->nesting_level && !$this->clone ) {

				// Close PHP code block
				echo '</div></code></pre>';
			}

		}

	}

}
