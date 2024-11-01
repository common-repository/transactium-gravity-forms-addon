<?php
include_once ('class-transactium-gf-addon-helpers.php');
GFForms::include_payment_addon_framework();
add_action('wp', array(
    'TransactiumGFAddon',
    'maybe_thankyou_page'
) , 5);

class TransactiumGFAddon extends GFPaymentAddOn
{
    protected $_version = TRANSACTIUM_GF_ADDON_VERSION;
    protected $_min_gravityforms_version = '2.1.1';
    protected $_slug = 'transactiumgravityforms';
    protected $_path = 'transactiumgravityforms/transactium.php';
    protected $_full_path = __FILE__;
    protected $_title = 'Transactium Add-On';
    protected $_short_title = 'Transactium';
    protected $_requires_credit_card = false;
    protected $_supports_callbacks = true;
    private static $_instance = null;
    protected $hps_ns = "http://transactium.com/HPP/";

    public static function get_instance()
    {
        if (self::$_instance == null)
        {
            self::$_instance = new TransactiumGFAddon();
        }
        return self::$_instance;
    }

    public function get_tr_settings()
    {
        return get_option('transactium_gfp_settings');
    }

    public function get_tr_setting($settingName)
    {
        return rgar($this->get_tr_settings() , $settingName);
    }

    public function init()
    {

        if (!TransactiumGFAddonHelpers::is_gravityforms_installed()) return;

        parent::init();

        //hooks for form settings
        add_action('gform_editor_js', array(
            'TransactiumGFAddonHelpers',
            'custom_attrib_editor_script'
        ));
        add_action('gform_field_advanced_settings', array(
            'TransactiumGFAddonHelpers',
            'render_field_custom_attrib_setting'
        ));

        add_filter('gform_notification_events', array(
            'TransactiumGFAddonHelpers',
            'notification_events'
        ) , 10, 2);
        add_action('gform_post_payment_action', array(
            'TransactiumGFAddonHelpers',
            'send_notifications'
        ) , 10, 2);

        //hook for admin page
        add_action('admin_init', array(
            $this,
            'admin_init'
        ));
    }

    public function admin_init()
    {

        $settings = get_option('transactium_gfp_settings');

        if (!in_array(RG_CURRENT_PAGE, array(
            'admin-ajax.php'
        )))
        {

            switch (RGForms::get('page'))
            {

                case 'gf_settings':

                    RGForms::add_settings_page('Transactium', array(
                        'TransactiumGFAddonHelpers',
                        'settings_page'
                    ));

                    add_filter('gform_tooltips', array(
                        'TransactiumGFAddonHelpers',
                        'gform_tooltips'
                    ));

                break;

                case 'gf_entries':

                    add_filter('gform_enable_entry_info_payment_details', '__return_false');

                break;
            }
        }

    }

    public function get_transactium_base_url()
    {
        $mode = $this->get_tr_setting('mode');
        return "https://psp." . ($mode != "live" ? "stg." : "") . "transactium.com/hps/webservice/hpws/v1500.asmx?WSDL";
    }

    public function get_page_base_url()
    {
        $pageURL = GFCommon::is_ssl() ? 'https://' : 'http://';

        $server_port = apply_filters('transactium_gf_return_url_port', $_SERVER['SERVER_PORT']);

        if ($server_port != '80')
        {
            $pageURL .= $_SERVER['SERVER_NAME'] . ':' . $server_port . $_SERVER['REQUEST_URI'];
        }
        else
        {
            $pageURL .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
        }
        return esc_url($pageURL);
    }

    public function return_url($form_id, $lead_id)
    {

        $pageURL = $this->get_page_base_url();

        $ids_query = "ids={$form_id}|{$lead_id}";
        $ids_query .= '&hash=' . wp_hash($ids_query);

        $url = add_query_arg('transactium_gf_return', base64_encode($ids_query) , $pageURL);

        $query = 'transactium_gf_return=' . base64_encode($ids_query);
        /**
         * Filters Transactium's return URL, which is the URL that users will be sent to after completing the payment.
         * Useful when URL isn't created correctly (could happen on some server configurations using PROXY servers).
         *
         * @since 2.4.5
         *
         * @param string  $url 	The URL to be filtered.
         * @param int $form_id	The ID of the form being submitted.
         * @param int $entry_id	The ID of the entry that was just created.
         * @param string $query	The query string portion of the URL.
         */
        return apply_filters('transactium_gf_return_url', $url, $form_id, $lead_id, $query);

    }

    public function redirect_url($feed, $submission_data, $form, $entry)
    {
        //validate all required settings are there
        if (!TransactiumGFAddonHelpers::check_settings_complete($this->get_tr_settings())) return;
        //filter url query paraemters
        $this_url = preg_replace('/\?.*/', '', $entry['source_url']);
        $this_url = rtrim($this_url, '/');

        //DO NOT MODIFY TIMEZONE - THIS IS TRANSACTIUM'S TIME ZONE
        $date = new DateTime("now", new DateTimeZone('Europe/Malta'));
        $date->add(new DateInterval("PT20M")); //ADD 20 minutes to compute valid until
        $validuntil = $date->format('Y-m-d\TH:i:s');

        $request = array(
            'URLs' => array(
                'PushURL' => add_query_arg(array(
                    'callback' => trim($this->_slug) ,
                    'reference' => urlencode($entry['id'] . '|' . wp_hash($entry['id']))
                ) , $this_url) ,
                'ApprovedURL' => $this->return_url($form['id'], $entry['id']) ,
                'DeclinedURL' => $this->return_url($form['id'], $entry['id'])
            ) ,
            'Client' => array(
                'ClientIPRestriction' => $entry['ip'] //customer IP
                
            ) ,
            'Tag' => $this->get_tr_setting('hps_tag') ,
            'Currency' => $entry['currency'],
            'RequireAllApproved' => 'false',
            'ValidUntil' => $validuntil,
            'TotalAmount' => "" . round($submission_data['payment_amount'] * 100) ,
            'Type' => 'Sale'
        );
        //fill details from feed settings
        $entries = array();

        $entries['Billing_Email'] = strtolower(trim($submission_data['email']));
        $entries['Billing_AddressUnitNumber'] = $submission_data['address'];
        $entries['Billing_StreetName'] = $submission_data['address2'];
        $entries['Billing_CityName'] = $submission_data['city'];
        $entries['Billing_TerritoryCode'] = $submission_data['state'];
        $entries['Billing_PostalCode'] = $submission_data['zip'];
        $entries['Billing_CountryCode'] = $submission_data['country'];

        foreach ($form['fields'] as $field)
        {

            if (!array_key_exists('fieldNamePOST', $field)) continue;

            $value = (isset($entry[$field['id']]) && !empty($entry[$field['id']])) ? $entry[$field['id']] : "";

            if (array_key_exists("inputs", $field) && !empty($field["inputs"]))
            {
                foreach ($field["inputs"] as $input)
                {
                    if (isset($entry[$input['id']]) && !empty($entry[$input['id']])) $value .= " " . $entry[$input['id']];
                }
            }

            if (!empty($value)) $entries[$field['fieldNamePOST']] = trim($value);
        }

        //merge request and extra details above
        $request = array_merge($request, TransactiumGFAddonHelpers::get_merchant_request($entries));

        //uncomment to DEBUG
        //error_log(json_encode($request));
        $hps_soap_url = $this->get_transactium_base_url();
        $hps_username = $this->get_tr_setting('hps_username');
        $hps_password = $this->get_tr_setting('hps_password');
        $hps_ns = $this->hps_ns;

        $soap = new SoapClient($hps_soap_url, array(
            'trace' => 1,
            'cache_wsdl' => 'wsdl_cache_none'
        ));
        $soap->__setSoapHeaders(new SOAPHeader($hps_ns, 'HPSAuthHeader', array(
            'Username' => $hps_username,
            'Password' => $hps_password
        )));
        $resp = $soap->CreateHostedPayment(array(
            'Request' => new SoapVar($request, SOAP_ENC_OBJECT, 'HPPCreateRequest', $hps_ns)
        ));
        //uncomment to DEBUG
        //error_log(json_encode($resp));
        return $resp
            ->CreateHostedPaymentResult->URL;
    }

    //decode custom field to get entry id and confirm it untampered with hash
    private function get_entry($custom_field)
    {
        //Getting entry associated with this IPN message (entry id is sent in the 'custom' field)
        //error_log($custom_field);
        list($entry_id, $hash) = explode('|', $custom_field);
        $hash_matches = wp_hash($entry_id) == $hash;

        $hash_matches = apply_filters('transactium_gf_hash_matches', $hash_matches, $entry_id, $hash, $custom_field);

        $this->log_debug(__METHOD__ . "(): IPN message has a valid custom field: {$custom_field}");

        $entry = GFAPI::get_entry($entry_id);

        if (is_wp_error($entry))
        {
            $this->log_error(__METHOD__ . '(): ' . $entry->get_error_message());

            return false;
        }

        return $entry;
    }

    //check amount is within reasonable tolerance of floating point
    private function is_valid_initial_payment_amount($entry_id, $amount_paid)
    {

        //get amount initially sent to transactium
        $amount_sent = gform_get_meta($entry_id, 'payment_amount');
        if (empty($amount_sent))
        {
            return true;
        }

        $epsilon = 0.00001;
        $is_equal = abs(floatval($amount_paid) - floatval($amount_sent)) < $epsilon;
        $is_greater = floatval($amount_paid) > floatval($amount_sent);

        //initial payment is valid if it is equal to or greater than product/subscription amount
        if ($is_equal || $is_greater)
        {
            return true;
        }

        return false;

    }

    //process payment notification (Transactium PUSH)
    private function process_ipn($config, $entry, $hps_resp, $hps_id)
    {
        $amount = null;
        $transaction_id = null;
        $status = $hps_resp['GetHostedPaymentResult']['Status'];
        $transaction_type = $hps_resp['GetHostedPaymentResult']['SubmittedRequest']['Type'];
        $amount = round($hps_resp['GetHostedPaymentResult']['TotalAmount'] / 100, 2);
        $formatted_amount = null;
        $reason = $hps_resp['GetHostedPaymentResult']['HPPMessage'];

        if (strtolower($transaction_type) != "sale" && $transaction_type != 0)
        {
            $this->log_debug(__METHOD__ . '(): Only sale transactions allowed.');
            return null;
        }

        $action = array();

        //handles products and donation
        switch (strtolower($status))
        {
            case 'success':
                //creates transaction
                $amount = round($hps_resp['GetHostedPaymentResult']['Transactions']['TransactionResponse']['Amount'] / 100, 2);
                $transaction_id = $hps_resp['GetHostedPaymentResult']['Transactions']['TransactionResponse']['TransactionID'];
                $formatted_amount = GFCommon::to_money($amount, $entry['currency']);

                $action['id'] = $transaction_id . '_' . $status;
                $action['type'] = 'complete_payment';
                $action['transaction_id'] = $transaction_id;
                $action['amount'] = $amount;
                $action['entry_id'] = $entry['id'];
                $action['payment_date'] = gmdate('y-m-d H:i:s');
                $action['payment_method'] = 'Transactium';

                if (!$this->is_valid_initial_payment_amount($entry['id'], $amount))
                {
                    //create note and transaction
                    $this->log_debug(__METHOD__ . '(): Payment amount does not match product price. Entry will not be marked as Approved.');
                    GFPaymentAddOn::add_note($entry['id'], sprintf(__('Payment amount (%s) does not match product price. Entry will not be marked as Approved. Transaction ID: %s', 'transactium-gravity-forms-addon') , $formatted_amount, $transaction_id));
                    GFPaymentAddOn::insert_transaction($entry['id'], 'payment', $transaction_id, $amount);

                    $action['abort_callback'] = true;
                }
                else
                {
                    $action['send_notifications'] = true;
                }
            break;

            case 'pending':
            case 'inprogress':
            case 'cancelled':
            case 'failure':
            case 'timeout':
            case 'error':
            default:
                $amount = round($hps_resp['GetHostedPaymentResult']['SubmittedRequest']['Transactions']['TransactionRequest']['Amount'] / 100, 2);
                $transaction_id = $hps_id;
                if (!empty($hps_resp['GetHostedPaymentResult']['Transactions']) && !empty($hps_resp['GetHostedPaymentResult']['Transactions']['TransactionResponse']) && !empty($hps_resp['GetHostedPaymentResult']['Transactions']['TransactionResponse']['TransactionID'])) $transaction_id = $hps_resp['GetHostedPaymentResult']['Transactions']['TransactionResponse']['TransactionID'];
                $formatted_amount = GFCommon::to_money($amount, $entry['currency']);

                $action['id'] = $transaction_id . '_' . $status;
                $action['type'] = 'fail_payment';
                $action['transaction_id'] = $transaction_id;
                $action['amount'] = $amount;
                $action['entry_id'] = $entry['id'];
                $action['note'] = sprintf(__('Payment was %s. Amount: %s. Transaction ID: %s. Reason: %s', 'transactium-gravity-forms-addon') , $status, $formatted_amount, $action['transaction_id'], $reason);
                break;

            }

            $entry['transaction_id'] = $action['transaction_id'];
            $entry['payment_amount'] = $action['amount'];
            if ($action['payment_date']) $entry['payment_date'] = $action['payment_date'];
            GFAPI::update_entry($entry);

            $this->log_debug(__METHOD__ . "(): Payment status: {$status} - Transaction Type: {$transaction_type} - Transaction ID: {$transaction_id} - Amount: {$formatted_amount} - Reason: {$reason}");

            return $action;
        }

        //handle external IPN (Transactium POST)
        public function callback()
        {
            $hps_ns = $this->hps_ns;

            $hps_id = rgget('hpsid');

            if (empty($hps_id))
            {
                $this->log_error(__METHOD__ . '(): IPN request does not include the hosted payment ID. Aborting.');
                return false;
            }

            $custom_field = rgget('reference');

            //------ Getting entry related to this IPN ----------------------------------------------//
            $entry = $this->get_entry(urldecode($custom_field));

            //Ignore orphan IPN messages (ones without an entry)
            if (!$entry)
            {
                $this->log_error(__METHOD__ . '(): Entry could not be found. Aborting.');

                return false;
            }
            $this->log_debug(__METHOD__ . '(): Entry has been found => ' . print_r($entry, true));

            if ($entry['status'] == 'spam')
            {
                $this->log_error(__METHOD__ . '(): Entry is marked as spam. Aborting.');

                return false;
            }

            $form = GFAPI::get_form($entry['form_id']);

            $hps_url = $this->get_transactium_base_url();
            $hps_username = $this->get_tr_setting('hps_username');
            $hps_password = $this->get_tr_setting('hps_password');

            if (empty($custom_field))
            {
                $this->log_error(__METHOD__ . '(): IPN request does not have a custom field, so it was not created by Gravity Forms. Aborting.');
                return false;
            }

            $soap = new SoapClient($hps_url, array(
                'trace' => 1,
                'cache_wsdl' => 'wsdl_cache_none'
            ));
            $soap->__setSoapHeaders(new SOAPHeader($hps_ns, 'HPSAuthHeader', array(
                'Username' => $hps_username,
                'Password' => $hps_password
            )));
            $hps_resp = $soap->GetHostedPayment(array(
                "HPSID" => $hps_id
            ));
            //uncomment to DEBUG
            //error_log(json_encode($hps_resp));
            if (!isset($hps_resp) || empty($hps_resp))
            {
                $this->log_debug(__METHOD__ . '(): IPN message verification failed');
                return false;
            }

            $this->log_debug(__METHOD__ . '(): IPN request received. Starting to process => ' . print_r($hps_resp, true));

            //------ Getting feed related to this IPN ------------------------------------------//
            $feed = $this->get_payment_feed($entry);

            //Ignore IPN messages from forms that are no longer configured with the Transactium add-on
            if (!$feed || !rgar($feed, 'is_active'))
            {
                $this->log_error(__METHOD__ . "(): Form no longer is configured with Transactium Addon. Form ID: {$entry['form_id']}. Aborting.");

                return false;
            }
            if ($feed['meta']['transactionType'] !== "product")
            {
                $this->log_error(__METHOD__ . "(): Only product transactions are supported. Form ID: {$entry['form_id']}. Aborting.");
                return false;
            }

            $this->log_debug(__METHOD__ . "(): Form {$entry['form_id']} is properly configured.");

            //----- Processing IPN ------------------------------------------------------------//
            $this->log_debug(__METHOD__ . '(): Processing IPN...');
            $action = $this->process_ipn($feed, $entry, json_decode(json_encode($hps_resp) , true) , $hps_id);
            $this->log_debug(__METHOD__ . '(): IPN processing complete.');

            //error_log(json_encode($action));
            if (rgempty('entry_id', $action))
            {
                return false;
            }

            return $action;

        }

        //Redirects to thank you or cancel page
        public static function maybe_thankyou_page()
        {

            $instance = self::get_instance();

            if (!$instance->is_gravityforms_supported())
            {
                return;
            }

            if ($str = rgget('transactium_gf_return'))
            {
                $str = base64_decode($str);

                parse_str($str, $query);
                if (wp_hash('ids=' . $query['ids']) == $query['hash'])
                {
                    list($form_id, $lead_id) = explode('|', $query['ids']);

                    $form = GFAPI::get_form($form_id);
                    $lead = GFAPI::get_entry($lead_id);

                    if (!class_exists('GFFormDisplay'))
                    {
                        require_once (GFCommon::get_base_path() . '/form_display.php');
                    }

                    //Leaving only required confirmation
                    $defaultConfirmationID = null;
                    $defaultConfirmationMessage = null;

                    foreach ($form['confirmations'] as $confirmationID => $confirmationData)
                    {
                        $confirmationName = trim($confirmationData['name']);
                        if ($form['confirmations'][$confirmationID]['isDefault'])
                        {
                            $defaultConfirmationID = $confirmationID;
                            $defaultConfirmationMessage = $form['confirmations'][$confirmationID];
                        }

                        if (!(strtolower($confirmationName) == "success" && strtolower($lead['payment_status']) == 'paid') && !(strtolower($confirmationName) == "failure" && strtolower($lead['payment_status']) == 'failed'))
                        {
                            unset($form['confirmations'][$confirmationID]);
                        }
                    }

                    if (empty($form['confirmations'])) $form['confirmations'][$defaultConfirmationID] = $defaultConfirmationMessage;

                    $confirmation = GFFormDisplay::handle_confirmation($form, $lead, false, array(
                        'transaction_id',
                        $lead['transaction_id']
                    ));

                    if (is_array($confirmation) && isset($confirmation['redirect']))
                    {
                        header("Location: {$confirmation['redirect']}");
                        exit;
                    }

                    GFFormDisplay::$submission[$form_id] = array(
                        'is_confirmation' => true,
                        'confirmation_message' => $confirmation,
                        'form' => $form,
                        'lead' => $lead
                    );
                }
            }
        }
    }

?>
