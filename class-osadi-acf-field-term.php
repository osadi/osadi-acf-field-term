<?php
/**
 * Osadi Term Field
 * 
 * Registers a new field type with ACF. This field makes it possible to set up a relation
 * to one or more taxonomy terms on the edit screen.
 * 
 */
class Osadi_ACF_Field_Term extends acf_field
{
	// Visible when selecting a field type.
	public $label = 'Term';

	// Internal name.
	public $name = 'osadi_acf_term';

	// List this field under category 'relational' in the select for field type.
	public $category = 'relational';

	/*
	 * Defaults for all registered fields.
	 * The key is the fields 'name'
	 */
	public $defaults = array (
		'allow_null'    => 1,
		'multiple'      => 0,
		'hide_empty'    => 1,
		'return_format' => 'taxonomy_id',
	);

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Render the fields on the Edit Field Group screen.
	 *
	 * @param array $field
	 */
	public function render_field_settings( $field )
	{
		// Allow null.
		acf_render_field_setting( $field, array(
			'label'        => __( 'Allow Null?', 'osadi-acf-term' ),
			'instructions' => '',
			'type'         => 'radio',
			'name'         => 'allow_null',
			'choices'      => array(
				1 => __( 'Yes', 'osadi-acf-term' ),
				0 => __( 'No', 'osadi-acf-term' ),
			),
			'layout'       => 'horizontal',
		));

		// Allow multiple values.
		acf_render_field_setting( $field, array(
			'label'        => __( 'Select multiple values?', 'osadi-acf-term' ),
			'instructions' => '',
			'type'         => 'radio',
			'name'         => 'multiple',
			'choices'      => array(
				1 => __( 'Yes', 'osadi-acf-term' ),
				0 => __( 'No', 'osadi-acf-term' ),
			),
			'layout'       => 'horizontal',
		));

		// Hide empty.
		acf_render_field_setting( $field, array(
			'label'        => __( 'Hide empty?', 'osadi-acf-term' ),
			'instructions' => '',
			'type'         => 'radio',
			'name'         => 'hide_empty',
			'choices'      => array(
				1 => __( 'Yes', 'osadi-acf-term' ),
				0 => __( 'No', 'osadi-acf-term' ),
			),
			'layout'       => 'horizontal',
		));

		// Setup choices for our return formats
		$return_format_choices = array(
			'id'          => __( 'Term ID', 'osadi-acf-term' ),
			'taxonomy_id' => __( 'Taxonomy name & Term ID', 'osadi-acf-term' ),
			'object'      => __( 'Term Object', 'osadi-acf-term' ),
		);

		if ( class_exists( 'Timber' ) ) {
			$return_format_choices['timber_object'] = __( 'Timber Term', 'osadi-acf-term' );
		}

		// Return format of term.
		acf_render_field_setting( $field, array(
			'label'        => __( 'Return format', 'osadi-acf-term' ),
			'instructions' => '',
			'type'         => 'radio',
			'name'         => 'return_format',
			'choices'      => $return_format_choices,
			'layout'       => 'horizontal',
		));
	}

	/**
	 * Render the field on the edit page/post/tax etc. screen.
	 *
	 * @param array $field Field data loaded from db.
	 */
	public function render_field( $field )
	{
		// Change field type from 'osadi_acf_term' to 'select'.
		$field['type']    = 'select';
		$field['ui']      = 1;
		$field['ajax']    = 0;
		$field['choices'] = $this->get_taxonomies_terms_assoc( $field['hide_empty'] );
		
		acf_render_field( $field );
	}

	/**
	 * Get all available taxonomies and associate them with all available terms.
	 *
	 * Borrowed heavily from the ACF api-helpers. @see acf_get_taxonomy_terms().
	 *
	 * @return array $taxonomies_terms
	 *   An array with the translated taxonomy name as key and the value as an array of terms.
	 *   Key for term is 'term_id:taxonomy' and value is term name.
	 */
	private function get_taxonomies_terms_assoc( $hide_empty )
	{
		$taxonomies       = acf_get_pretty_taxonomies();
		$taxonomies_terms = array();

		foreach ( array_keys( $taxonomies ) as $taxonomy ) {
		
			$label = $taxonomies[ $taxonomy ];
			$terms = get_terms( $taxonomy, array( 'hide_empty' => $hide_empty ) );

			if ( ! empty( $terms ) ) {
				$taxonomies_terms[ $label ] = array();
				
				foreach ( $terms as $term ) {
					$key                                = "{$term->term_id}:{$taxonomy}"; 
					$taxonomies_terms[ $label ][ $key ] = $term->name;
				}
			}
		}

		return $taxonomies_terms;
	}

	/**
	 * Enqueue our javascript.
	 * 
	 * This action seems to be called in:
	 * @see acf_input_listener::__construct()
	 * @see acf_admin_field_group::admin_enqueue_scripts()
	 * 
	 */
	public function input_admin_enqueue_scripts() 
	{
		$dir          = plugin_dir_url( __FILE__ );
		$handle       = str_replace( '_', '-', $this->name );
		$dependencies = array( 'acf-input' );

		wp_register_script( $handle, "{$dir}js/{$handle}.js", $dependencies, $version = false, $in_footer = true );
		wp_localize_script( $handle, $this->name, array( 'field_name' => $this->name ) );
		wp_enqueue_script( $handle );
	}

	/**
	*  This filter is applied to the $value after it is loaded from the db and before it is returned to the template.
	*  
	*  If 'return_format' is 'object' we change the value to contain the term objects instead of IDs.
	*
	*  @type filter
	*
	*  @param mixed  $value   The value which was loaded from the database.
	*  @param mixed  $post_id The $post_id from which the value was loaded.
	*  @param array  $field   All the field options loaded from db.
	*  @return array $terms   The modified value.
	*/
	
	public function format_value( $value, $post_id, $field )
	{
		if ( empty( $value ) ) {
			return $value;
		}
		
		$terms = array();

		// Wrap our string in an array.
		if ( is_string( $value ) ) {
			$value = array( $value );
		}

		foreach ( $value as $id_taxonomy ) {
			list( $term_id, $taxonomy ) = explode( ':', $id_taxonomy );

			switch ( $field['return_format'] ) {
				case 'object':
						$term = get_term( intval( $term_id ), $taxonomy );
						if ( ! is_wp_error( $term ) && is_object( $term ) ) {
							$terms[] = $term;
						}
					break;
				case 'timber_object':
						$term = Timber::get_terms( $taxonomy, array(
							'include' => array( intval( $term_id ) )
						));
						if ( ! is_wp_error( $term ) && is_array( $term ) ) {
							$terms[] = $term[0];
						}
					break;
				case 'taxonomy_id':
					$terms[$taxonomy][] = intval( $term_id );
					break;
				default:
					$terms[] = intval( $term_id );
			}
		}

		return $terms;
	}
}

new Osadi_ACF_Field_Term();
