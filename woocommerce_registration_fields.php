<?php 
/**
 * Plugin Name: Woocommerce Registration Fields
 * Plugin URI: http://facebook.com/Alamdeveloper
 * Description: Woocommerce Registration Fields
 * Version: 1.0.0
 * Author: Mohd Alam
 * Author URI: http://facebook.com/Alamdeveloper
 * License: IPL
 */


/**
*       Adding Registration fields to the form 
**/
add_action( 'register_form', 'adding_custom_registration_fields' );
function adding_custom_registration_fields( ) {

	//lets make the field required so that i can show you how to validate it later;
	$firstname = empty( $_POST['firstname'] ) ? '' : $_POST['firstname'];
	$lastname  = empty( $_POST['lastname'] ) ? '' : $_POST['lastname'];
	$phone = empty( $_POST['phone'] ) ? '' : $_POST['phone'];
	?>
	<script>
		function isNumber(evt) {
		    evt = (evt) ? evt : window.event;
		    var charCode = (evt.which) ? evt.which : evt.keyCode;
		    if (charCode > 31 && (charCode < 48 || charCode > 57)) {
		        return false;
		    }
		    return true;
		}
		function ValidateAlpha(evt){
	        var keyCode = (evt.which) ? evt.which : evt.keyCode
	        if ((keyCode < 65 || keyCode > 90) && (keyCode < 97 || keyCode > 123) && keyCode != 32)
	        {
	        	return false;
	        }else{	
	            return true;
	        }    
   		 }
	</script>
	<div class="form-row form-row-wide">
		<label for="reg_firstname"><?php _e( 'First Name', 'woocommerce' ) ?><span class="required">*</span></label>
		<input type="text" class="input-text" name="firstname" id="reg_firstname" size="30" value="<?php echo esc_attr( $firstname ) ?>" onkeypress="return ValidateAlpha(event)"/>
	</div>
	<div class="form-row form-row-wide">
		<label for="reg_lastname"><?php _e( 'Last Name', 'woocommerce' ) ?><span class="required">*</span></label>
		<input type="text" class="input-text" name="lastname" id="reg_lastname" size="30" value="<?php echo esc_attr( $lastname ) ?>" onkeypress="return ValidateAlpha(event)"/>
	</div>
	<div class="form-row form-row-wide">
		<label for="reg_phone"><?php _e( 'Phone', 'woocommerce' ) ?><span class="required">*</span></label>
		<input type="text" class="input-text" name="phone" id="reg_phone" size="10" onkeypress="return isNumber(event)" value="<?php echo esc_attr( $phone ) ?>" maxlength='10'/>
	</div><?php
}



/** 
* 		Validation registration form  after submission using the filter registration_errors
**/
add_filter( 'woocommerce_registration_errors', 'registration_errors_validation' );

/**
 * @param WP_Error $reg_errors
 *
 * @return WP_Error
 */
function registration_errors_validation( $reg_errors ) {

	if ( empty( $_POST['firstname'] ) ) {
		$reg_errors->add( 'empty required fields', __( 'Please Enter First Name.', 'woocommerce' ) );
	}
	else if ( empty( $_POST['lastname'] )) {
		$reg_errors->add( 'empty required fields', __( 'Please Enter Last Name.', 'woocommerce' ) );
	}
	else if ( empty( $_POST['phone'] )) {
		$reg_errors->add( 'empty required fields', __( 'Please Enter Phone No.', 'woocommerce' ) );
	}
	else if(count($_POST['phone'])!=10 ){
		$reg_errors->add( 'empty required fields', __( 'Phone Length Should Be 10.', 'woocommerce' ) );	
	}

	return $reg_errors;
}



/** 
* 		Updating use meta after registration successful registration
**/
add_action('woocommerce_created_customer','adding_extra_reg_fields');

function adding_extra_reg_fields($user_id) {
	extract($_POST);
	update_user_meta($user_id, 'first_name', sanitize_text_field( $firstname ));
	update_user_meta($user_id, 'last_name', sanitize_text_field( $lastname ));
	update_user_meta($user_id, 'phone', sanitize_text_field( $phone ));
	update_user_meta($user_id, 'billing_first_name', sanitize_text_field( $firstname ));
	update_user_meta($user_id, 'shipping_first_name', sanitize_text_field( $firstname ));
	update_user_meta($user_id, 'billing_last_name', sanitize_text_field( $lastname ));
	update_user_meta($user_id, 'shipping_last_name', sanitize_text_field( $lastname ));
	update_user_meta($user_id, 'billing_phone', sanitize_text_field( $phone ));
}


/**
*		 ADD CONFIRM PASSWORD FIELD ON REGISTRATION
**/
add_filter('woocommerce_registration_errors', 'registration_errors_validation2', 10,3);
function registration_errors_validation2($reg_errors, $sanitized_user_login, $user_email) {
	global $woocommerce;
	extract( $_POST );
	if ( strcmp( $password, $password2 ) !== 0 ) {
		return new WP_Error( 'registration-error', __( 'Both Passwords Not Matched.', 'woocommerce' ) );
	}
	return $reg_errors;
}
add_action( 'woocommerce_register_form', 'wc_register_form_password_repeat' );
function wc_register_form_password_repeat() { ?>
	<p class="form-row form-row-wide">
		<label for="reg_password2"><?php _e( 'Confirm Password', 'woocommerce' ); ?> <span class="required">*</span></label>
		<input type="password" class="input-text" name="password2" id="reg_password2" value="<?php if ( ! empty( $_POST['password2'] ) ) echo esc_attr( $_POST['password2'] ); ?>" />
	</p>
	<?php
}





/**
*		ADD RE ENTER PASSWORD FIELD PASSWORD FIELD ON CHECKOUT
**/
add_action( 'woocommerce_checkout_init', 'wc_add_confirm_password_checkout', 10, 1 );
function wc_add_confirm_password_checkout( $checkout ) {
	if ( get_option( 'woocommerce_registration_generate_password' ) == 'no' ) {
		$checkout->checkout_fields['account']['account_password2'] = array(
			'type' 				=> 'password',
			'label' 			=> __( 'Confirm password', 'woocommerce' ),
			'required'          => true,
			'placeholder' 		=> _x( 'Confirm Password', 'placeholder', 'woocommerce' )
		);
	}
}


/**
*		 Check the password and confirm password fields match before allow checkout to proceed.
**/
add_action( 'woocommerce_after_checkout_validation', 'wc_check_confirm_password_matches_checkout', 10, 2 );
function wc_check_confirm_password_matches_checkout( $posted ) {
	$checkout = WC()->checkout;
	if ( ! is_user_logged_in() && ( $checkout->must_create_account || ! empty( $posted['createaccount'] ) ) ) {
		if ( strcmp( $posted['account_password'], $posted['account_password2'] ) !== 0 ) {
			wc_add_notice( __( 'Both Password Not Matched.', 'woocommerce' ), 'error' );
		}
	}
}
?>