/**
 * Tried to use this with the acf.add_action('ready', fn(){}) as seen here:
 * http://www.advancedcustomfields.com/resources/adding-custom-javascript-fields/
 *
 * Problem was that the custom field didn't register at the right time so the select2 wasn't rendered.
 *
 * Registering it in jQuerys document ready seems to do the trick.
 *
 * var osadi_acf_term.field_name is set with wp_localize_script()
 *
 */

(function($){
	var Field = acf.models.SelectField.extend({
		type: osadi_acf_term.field_name,
	});
	acf.registerFieldType( Field );
})(jQuery);