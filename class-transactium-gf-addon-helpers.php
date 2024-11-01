<?php

class TransactiumGFAddonHelpers
{

    public static function is_gravityforms_installed()
    {
        if (!function_exists('is_plugin_active') || !function_exists('is_plugin_active_for_network'))
        {
            require_once (ABSPATH . '/wp-admin/includes/plugin.php');
        }
        if (is_multisite())
        {
            return (is_plugin_active_for_network('gravityforms/gravityforms.php') || is_plugin_active('gravityforms/gravityforms.php'));
        }
        else
        {
            return is_plugin_active('gravityforms/gravityforms.php');
        }
    }

    static $custom_attributes_container_label = "Transactium";
    static $field_special_type_default = "off";
    static $custom_attributes = array(
        "field_name_POST" => array(
            "gravitySettingLabel" => "Field Name for POST",
            "gravitySettingType" => "text",
            "fieldProperty" => "fieldNamePOST",
            "fieldAttribute" => "",
            "fieldAttributeOverrider" => "",
            "fieldAsSettingOnly" => true,
        ) ,
    );

    public static function custom_attrib_editor_script()
    {

        $custom_attributes = TransactiumGFAddonHelpers::$custom_attributes;
        $field_special_type_default = TransactiumGFAddonHelpers::$field_special_type_default;
?>
	  <script type="text/javascript">
		// Add .field_id_setting onto the end of each field type's properties.
		
		<?php foreach ($custom_attributes as $fieldRef => $fieldProps)
        { ?>
		
			jQuery.map(fieldSettings, function (el, i) {
			  fieldSettings[i] += ', .<?php echo esc_js($fieldRef); ?>_setting';
			});
		 
			// Populate field settings on initialization.
			jQuery(document).on('gform_load_field_settings', function(ev, field){
			  var element = jQuery(document.getElementById('field_<?php echo esc_js($fieldRef); ?>')); 
			  if (element.attr('type') == "checkbox") {
				element.val(parseInt(field.<?php echo esc_js($fieldProps['fieldProperty']); ?>));
				element.prop("checked", field.<?php echo esc_js($fieldProps['fieldProperty']); ?> == "1" ? true : false);
			  } else if (element.attr('type') == "select" || element.localName == "select") {
				element.find("option[value='"+(field.<?php echo esc_js($fieldProps['fieldProperty']); ?> || '<?php echo esc_js($field_special_type_default); ?>')+"']").attr("selected","selected");
			  } else {
				element.val(field.<?php echo esc_js($fieldProps['fieldProperty']); ?> || '');
			  }
			});
			
		<?php
        } ?>
	  </script>
	<?php
    }

    public static function render_field_custom_attrib_setting($position)
    {
        $custom_attributes = TransactiumGFAddonHelpers::$custom_attributes;
        $custom_attributes_container_label = TransactiumGFAddonHelpers::$custom_attributes_container_label;

        if (0 !== $position)
        {
            return;
        }
?>

		<style>
			fieldset {
				display: block;
				-webkit-margin-start: 2px;
				-webkit-margin-end: 2px;
				-webkit-padding-before: 0.35em;
				-webkit-padding-start: 0.75em;
				-webkit-padding-end: 0.75em;
				-webkit-padding-after: 0.625em;
				min-width: -webkit-min-content;
				border-width: 2px;
				border-style: groove;
				border-color: #599db7;
				border-image: initial;
			}
			fieldset legend {
				background-color: #D2E0EB;
			}
		</style>
		<?php if (!empty($custom_attributes_container_label))
        { ?>
			<fieldset class="container" id="container" style="border-width:6px; padding: 1em;">
			  <legend>
			  <?php echo esc_html($custom_attributes_container_label); ?>
			  </legend>
		<?php
        } ?>
				<?php foreach ($custom_attributes as $fieldRef => $fieldProps)
        { ?>
					<li class="<?php echo esc_attr($fieldRef); ?>_setting field_setting" style="display: list-item;">
						<label for="field_<?php echo esc_attr($fieldRef); ?>" class="section_label">
						  <?php echo esc_html($fieldProps['gravitySettingLabel']); ?>
						</label>

						<?php if ($fieldProps['gravitySettingType'] == "checkbox")
            { ?>
							<input type="<?php echo esc_attr($fieldProps['gravitySettingType']); ?>" id="field_<?php echo esc_attr($fieldRef); ?>" onchange="jQuery(this).val(jQuery(this).prop('checked') == true ? 1 : 0); SetFieldProperty('<?php echo esc_attr($fieldProps['fieldProperty']); ?>', this.value);"  />
						<?php
            }
            else if ($fieldProps['gravitySettingType'] == "select")
            { ?>
							<select type="<?php echo esc_attr($fieldProps['gravitySettingType']); ?>" id="field_<?php echo esc_attr($fieldRef); ?>" onchange="SetFieldProperty('<?php echo esc_attr($fieldProps['fieldProperty']); ?>', jQuery(this).val());">
								<?php foreach ($fieldProps['gravitySettingValues'] as $value => $valueProps)
                { ?>
									<option value="<?php echo esc_attr($value); ?>"><?php echo esc_attr($value); ?></option>
								<?php
                } ?>
							</select>
						<?php
            }
            else
            { ?>
							<input type="<?php echo esc_attr($fieldProps['gravitySettingType']); ?>" id="field_<?php echo esc_attr($fieldRef); ?>" onchange="SetFieldProperty('<?php echo esc_attr($fieldProps['fieldProperty']); ?>', this.value);" class="fieldwidth-3" size="35" />
						<?php
            } ?>
					</li>
				<?php
        } ?>
		<?php if (!empty($custom_attributes_container_label))
        { ?>
			</fieldset>
		<?php
        } ?>
		<br>

	<?php
    }

    public static function settings_page()
    {

        if (isset($_POST['transactium_gfp_submit']))
        {
            check_admin_referer('update', 'transactium_gfp_update');
            $settings = array(
                'mode' => rgpost('transactium_gfp_mode') ,
                'hps_username' => trim(rgpost('transactium_gfp_hps_username')) ,
                'hps_password' => trim(rgpost('transactium_gfp_hps_password')) ,
                'hps_tag' => trim(rgpost('transactium_gfp_hps_tag'))
            );
            $settings = apply_filters('transactium_gfp_save_settings', $settings);
            update_option('transactium_gfp_settings', $settings);
        }
        else if (has_filter('transactium_gfp_settings_page_action'))
        {
            $do_return = '';
            $do_return = apply_filters('transactium_gfp_settings_page_action', $do_return);
            if ($do_return)
            {
                return;
            }
        }

        $settings = get_option('transactium_gfp_settings');

?>
		
		<form method="post" action="">
			<?php wp_nonce_field('update', 'transactium_gfp_update') ?>

			<h3><span class="icon-transactium"></span><?php _e(' Transactium Settings', 'transactium-gravity-forms-addon'); ?></h3>

			<div class="account-information-settings">
				<p style="text-align: left;">
					<?php echo sprintf(__("Transactium is a payment gateway for merchants. Use Gravity Forms to collect payment information and automatically integrate to your client's Transactium account.", 'transactium-gravity-forms-addon')) ?>
				</p>
				<table class="form-table">
					<tr>
						<th scope="row" nowrap="nowrap"><label
								for="transactium_gfp_mode"><?php _e('API Mode', 'transactium-gravity-forms-addon'); ?> <?php gform_tooltip('transactium_api') ?></label>
						</th>
						<td width="88%">
							<input type="radio" name="transactium_gfp_mode" id="transactium_gfp_mode_staging"
							                          value="staging" <?php echo 'staging' == rgar($settings, 'mode') ? "checked='checked'" : '' ?>/>
							<label class="inline"
							       for="transactium_gfp_mode_staging"><?php _e('Staging', 'transactium-gravity-forms-addon'); ?></label>
							
							&nbsp;&nbsp;&nbsp; <input type="radio" name="transactium_gfp_mode" id="transactium_gfp_mode_live"
							       value="live" <?php echo rgar($settings, 'mode') != 'staging' ? "checked='checked'" : '' ?>/>
							<label class="inline"
							       for="transactium_gfp_mode_live"><?php _e('Live', 'transactium-gravity-forms-addon'); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row" nowrap="nowrap"><label
								for="transactium_gfp_hps_username"><?php _e('HPS Username', 'transactium-gravity-forms-addon'); ?> <?php gform_tooltip('transactium_hps_username') ?></label>
						</th>
						<td width="88%">
							<input id="transactium_gfp_hps_username" style="width: 25em;"
							       name="transactium_gfp_hps_username"
							       value="<?php echo trim(esc_attr(rgar($settings, 'hps_username'))) ?>"/>
							<br/>
						</td>
					</tr>
					<tr>
						<th scope="row" nowrap="nowrap"><label
								for="transactium_gfp_hps_password"><?php _e('HPS Password', 'transactium-gravity-forms-addon'); ?> <?php gform_tooltip('transactium_hps_password') ?></label>
						</th>
						<td width="88%">
							<input id="transactium_gfp_hps_password" style="width: 25em;" type="password"
								   name="transactium_gfp_hps_password"
								   value="<?php echo trim(esc_attr(rgar($settings, 'hps_password'))) ?>"/>
							<br/>
						</td>
					</tr>
					<tr>
						<th scope="row" nowrap="nowrap"><label
								for="transactium_gfp_hps_tag"><?php _e('HPS Tag', 'transactium-gravity-forms-addon'); ?> <?php gform_tooltip('transactium_hps_tag') ?></label>
						</th>
						<td width="88%">
							<input id="transactium_gfp_hps_tag" style="width: 25em;"
							       name="transactium_gfp_hps_tag"
							       value="<?php echo trim(esc_attr(rgar($settings, 'hps_tag'))) ?>"/>
							<br/>
						</td>
					</tr>
				</table>
				<br/>
			</div>
			<?php
        do_action('transactium_gfp_settings_page', $settings);
?>
			<p class="submit" style="text-align: left;">
				<input type="submit" name="transactium_gfp_submit" class="button-primary"
				       value="<?php _e('Save Settings', 'transactium-gravity-forms-addon') ?>"/>
			</p>
		</form>

	<?php
    }

    public static function gform_tooltips($tooltips)
    {
        $transactium_tooltips = array(
            'transactium_api' => '<h6>' . __('API Mode', 'transactium-gravity-forms-addon') . '</h6>' . __('Select the Transactium API Mode you would like to use.', 'transactium-gravity-forms-addon') ,
            'transactium_hps_username' => '<h6>' . __('HPS Username', 'transactium-gravity-forms-addon') . '</h6>' . __('Enter the Username of your Transactium HPS account.', 'transactium-gravity-forms-addon') ,
            'transactium_hps_password' => '<h6>' . __('HPS Password', 'transactium-gravity-forms-addon') . '</h6>' . __('Enter the Password of your Transactium HPS account.', 'transactium-gravity-forms-addon') ,
            'transactium_hps_tag' => '<h6>' . __('HPS Tag', 'transactium-gravity-forms-addon') . '</h6>' . __('Enter a Tag which uniquely identifies this site.', 'transactium-gravity-forms-addon')
        );

        return array_merge($tooltips, $transactium_tooltips);
    }

    public static function check_settings_complete($settings)
    {
        $all_fields = array(
            "mode",
            "hps_username",
            "hps_password",
            "hps_tag"
        );
        $required = array(
            "mode",
            "hps_username",
            "hps_password",
            "hps_tag"
        );

        $missing = array();

        foreach ($all_fields as $fieldName)
        {
            $settings[$fieldName] = trim($settings[$fieldName]);
            if (in_array($fieldName, $required) && (!isset($settings[$fieldName]) || empty($settings[$fieldName]))) $missing[] = $fieldName;
        }

        if (!empty($missing))
        {
            echo '<div class="error"><p>' . __('Transactium Gravity Forms Plugin: Required form settings are not set.', 'transactium-gravity-forms-addon') . '</p></div>';
            return false;
        }
        else
        {
            return true;
        }

        return empty($missing);

    }

    public static function get_merchant_request($entries)
    {

        $editable_request = array(
            "Client" => array(
                "ClientReference",
                "OrderReference",
                "MerchantUniqueReference",
                "CardHolderName"
            ) ,
            "Appearance" => array(
                "ShopName",
                "SkinFolder",
                "LanguageCode",
                "SiteBGColor"
            ) ,
            "Billing" => array(
                "FullName",
                "Phone",
                "Email",
                "StreetNumber",
                "StreetName",
                "AddressUnitNumber",
                "CityName",
                "TerritoryCode",
                "CountryCode",
                "PostalCode",
                "Fax",
                "BirthDateYYYYMMDD"
            ) ,
            "Customer" => array(
                "FullName",
                "Phone",
                "Email",
                "StreetNumber",
                "StreetName",
                "AddressUnitNumber",
                "CityName",
                "TerritoryCode",
                "CountryCode",
                "PostalCode",
                "Fax",
                "BusinessName",
                "BusinessRegistrationNumber",
                "BusinessTaxNumber"
            ) ,
            "Shipping" => array(
                "FullName",
                "Phone",
                "Email",
                "StreetNumber",
                "StreetName",
                "AddressUnitNumber",
                "CityName",
                "TerritoryCode",
                "CountryCode",
                "PostalCode",
                "Fax",
                "BusinessName"
            )
        );

        $request = array();

        foreach ($entries as $postName => $value)
        {
            if (!isset($value) || empty($value)) continue;

            $postNameEntries = array_map('trim', explode(',', $postName));
            foreach ($postNameEntries as $postNameEntry)
            {
                $varTree = explode("_", $postNameEntry);
                if (sizeof($varTree) == 2 && in_array($varTree[1], $editable_request[$varTree[0]]))
                {
                    if (!isset($request[$varTree[0]])) $request[$varTree[0]] = array();

                    $request[$varTree[0]][$varTree[1]] = $value;
                }
            }
        }

        return $request;
    }

    public static function notification_events($notification_events, $form)
    {
        $has_transactium_feed = function_exists('transactium_gf_addon') ? transactium_gf_addon()->get_feeds($form['id']) : false;

        if ($has_transactium_feed)
        {
            $payment_events = array(
                'complete_payment' => __('Payment Completed', 'gravityforms') ,
                'refund_payment' => __('Payment Refunded', 'gravityforms') ,
                'fail_payment' => __('Payment Failed', 'gravityforms') ,
                'add_pending_payment' => __('Payment Pending', 'gravityforms') ,
                'void_authorization' => __('Authorization Voided', 'gravityforms') ,
                'create_subscription' => __('Subscription Created', 'gravityforms') ,
                'cancel_subscription' => __('Subscription Canceled', 'gravityforms') ,
                'expire_subscription' => __('Subscription Expired', 'gravityforms') ,
                'add_subscription_payment' => __('Subscription Payment Added', 'gravityforms') ,
                'fail_subscription_payment' => __('Subscription Payment Failed', 'gravityforms') ,
            );

            return array_merge($notification_events, $payment_events);
        }

        return $notification_events;
    }

    public static function send_notifications($entry, $action)
    {
        $form = GFAPI::get_form($entry['form_id']);
        GFAPI::send_notifications($form, $entry, rgar($action, 'type'));
    }
}
?>
