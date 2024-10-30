<?php

namespace MF;

use WC_Payment_Gateways;


class SettingPanel
{

    public function __construct()
    {
        add_action('woocommerce_settings_start', array($this, 'add_woo_settings'));
        add_filter('woocommerce_settings_tabs_array', array($this, 'add_woo_settings_tab'), 30);
        add_action('woocommerce_settings_tabs_wc_billdu', array($this, 'add_woo_settings_tab_content'));
        add_action('woocommerce_settings_save_wc_billdu', array($this, 'save_woo_settings_tab_content'));
    }


    function add_woo_settings()
    {
        global $woocommerce_settings;
        $woocommerce_settings['wc_billdu'] = apply_filters('woocommerce_wc_billdu_settings', $this->get_settings());
    }

    function add_woo_settings_tab($tabs)
    {
        $tabs['wc_billdu'] = __('Billdu', 'wc-billdu');

        return $tabs;
    }

    function add_woo_settings_tab_content()
    {
        global $woocommerce_settings;
        echo '<img src="https://my.billdu.com/images/logo_color_com.png">';
        woocommerce_admin_fields($woocommerce_settings['wc_billdu']);
    }


    function save_woo_settings_tab_content()
    {
        if (class_exists('WC_Admin_Settings')) {
            global $woocommerce_settings;
            $woocommerce_settings['wc_billdu'] = apply_filters('woocommerce_wc_billdu_settings', $this->get_settings());
            \WC_Admin_Settings::save_fields($woocommerce_settings['wc_billdu']);
        }
    }

    function get_settings()
    {
        $invoice_settings = array(
            array(
                'title' => __('Authorization', 'wc-billdu'),
                'type' => 'title',
                'id' => 'woocommerce_mf_invoice_title1'
            ),
            array(
                'title' => __('Endpoint', 'wc-billdu'),
                'id' => 'woocommerce_mf_endpoint',
                'type' => 'radio',
                'desc' => '',
                'default' => 'sk',
                'options' => [
                    'com' => 'api.billdu.com',
                    'sk' => 'api.minifaktura.sk',
                    'cz' => 'api.minifaktura.cz',
                ]
            ),
            array(
                'title' => __('API Key', 'wc-billdu'),
                'id' => 'woocommerce_mf_apikey',
                'desc' => '',
                'class' => 'input-text regular-input',
                'type' => 'text',
            ),
            array(
                'title' => __('API Secret', 'wc-billdu'),
                'id' => 'woocommerce_mf_apisecret',
                'desc' => '',
                'class' => 'input-text regular-input',
                'type' => 'text',
            ),

            array(
                'type' => 'sectionend',
                'id' => 'woocommerce_wi_invoice_invoice_options'
            ),
            array(
                'title' => __('Invoice Creation', 'wc-billdu'),
                'type' => 'title',
                'desc' => '',
                'id' => 'woocommerce_mf_invoice_title2'
            )
        );

        $wc_get_order_statuses = $this->get_order_statuses();
        $shop_order_status = array('0' => __('Don\'t generate', 'wc-billdu'));
        $shop_order_status = array_merge($shop_order_status, $wc_get_order_statuses);
        $gateways = WC()->payment_gateways->payment_gateways();

        foreach ($gateways as $gateway) {
            $invoice_settings[] = array(
                'title' => $gateway->title,
                'id' => 'woocommerce_mf_document_invoice_' . $gateway->id,
                'default' => 0,
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'options' => $shop_order_status
            );
        }
        $invoice_settings[] = array(
            'type' => 'sectionend',
            'id' => 'woocommerce_wi_invoice_title4'
        );

        $invoice_settings[] = array(
            'title' => __('Proforma  Creation', 'wc-billdu'),
            'type' => 'title',
            'desc' => 'Select when you would like to create a proforma invoice for each payment gateway.',
            'id' => 'woocommerce_wi_invoice_title5'
        );

        foreach ($gateways as $gateway) {
            $invoice_settings[] = array(
                'title' => $gateway->title,
                'id' => 'woocommerce_mf_document_proforma_' . $gateway->id,
                'default' => 0,
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'options' => $shop_order_status
            );
        }

        $invoice_settings[] = array(
            'type' => 'sectionend',
            'id' => 'woocommerce_wi_invoice_title4'
        );

        $invoice_settings[] = array(
            'title' => __('Invoice  email', 'wc-billdu'),
            'type' => 'title',
            'id' => 'woocommerce_wi_invoice_title54'
        );

        $invoice_settings[] = array(
            'title' => __('Send email', 'wc-billdu'),
            'id' => 'woocommerce_mf_sent_email',
            'default' => 'no',
            'type' => 'checkbox',
            'desc' => ''
        );

        $invoice_settings[] = array(
            'title' => __('Email Subject', 'wc-billdu'),
            'id' => 'woocommerce_mf_email_subject',
            'default' => '',
            'type' => 'text',
            'desc' => ''
        );
        $invoice_settings[] = array(
            'title' => __('Email Message', 'wc-billdu'),
            'id' => 'woocommerce_mf_email_message',
            'default' => '',
            'type' => 'textarea',
            'desc' => 'You can use tags #DOCUMENT_NAME# #NUMBER# #CLIENT_NAME# #COMPANY_NAME# #TOTAL# to subject and email body. These tags will be replaced by real values retrieved from the relevant invoice.
'
        );

        $invoice_settings[] = array(
            'type' => 'sectionend',
            'id' => 'woocommerce_wi_invoice_title3'
        );

        $invoice_settings[] = array(
            'type' => 'sectionend',
            'id' => 'woocommerce_wi_invoice_title5'
        );

        $invoice_settings[] = array(
            'title' => __('Payment Methods', 'wc-billdu'),
            'type' => 'title',
            'desc' => 'Map Woocommerce payment methods to ones in billdu',
            'id' => 'woocommerce_wi_invoice_title6'
        );

        $gateway_mapping = array(
            '0' => __('Don\'t use', 'wc-billdu'),
            'transfer' => __('Transfer', 'wc-billdu'),
            'cash' => __('Cash', 'wc-billdu'),
            'cash_on_delivery' => __('Cash on delivery', 'wc-billdu'),
            'paypal' => __('Paypal', 'wc-billdu'),
            'credit_card' => __('Credit card', 'wc-billdu'),
            'advance_payment' => __('Advance Payment', 'wc-billdu'),
            'direct_debit' => __('Direct debit', 'wc-billdu'),
            'cheque' => __('Cheque', 'wc-billdu'),
            'pigeon' => __('Pigeon', 'wc-billdu'),
            'net_10' => __('Net 10', 'wc-billdu'),
            'net_15' => __('Net 15', 'wc-billdu'),
            'net_30' => __('Net 30', 'wc-billdu'),
            'net_60' => __('Net 60', 'wc-billdu'),
            'net_90' => __('Net 90', 'wc-billdu'),
        );

        foreach ($gateways as $gateway) {
            $invoice_settings[] = array(
                'title' => $gateway->title,
                'id' => 'woocommerce_mf_gateway_' . $gateway->id,
                'default' => 0,
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'options' => $gateway_mapping
            );
        }

        $invoice_settings[] = array(
            'type' => 'sectionend',
            'id' => 'woocommerce_wi_invoice_title5'
        );

        $invoice_settings[] = array(
            'title' => __('Mark Invoice as Paid', 'wc-billdu'),
            'type' => 'title',
            'desc' => '',
            'id' => 'woocommerce_wi_invoice_title6'
        );

        $shop_order_status['0'] = 'Never';

        $invoice_settings[] = array(
            'title' => __('Mark Invoice as Paid', 'wc-billdu'),
            'type' => 'select',
            'desc' => '',
            'id' => 'woocommerce_mf_add_payment',
            'options' => $shop_order_status
        );

        $invoice_settings[] = array(
            'type' => 'sectionend',
            'id' => 'woocommerce_wi_invoice_title4'
        );

        $invoice_settings[] = array(
            'title' => __('Aditional  settings', 'wc-billdu'),
            'type' => 'title',
            'id' => 'woocommerce_wi_invoice_title54e'
        );
        $invoice_settings[] = array(
            'title' => __('Item Name', 'wc-billdu'),
            'desc' => sprintf(__('Available Tags: %s'), '[ATTRIBUTES], [NON_VARIATIONS_ATTRIBUTES], [VARIATION], [SHORT_DESCRIPTION], [SKU]'),
            'id' => 'woocommerce_mf_item_name',
            'css' => 'width:50%; height: 75px;',
            'default' => '[ATTRIBUTES] [SHORT_DESCRIPTION]',
            'type' => 'textarea',
        );
        $invoice_settings[] = array(
            'title' => __('Shipping Item Name', 'wc-billdu'),
            'id' => 'woocommerce_mf_shipping_item_name',
            'default' => 'Poštovné',
            'desc' => '',
            'type' => 'text',
        );

        $invoice_settings[] = array(
            'type' => 'sectionend',
            'id' => 'woocommerce_wi_invoice_title6'
        );
        return $invoice_settings;
    }

    function get_order_statuses()
    {
        if (function_exists('wc_order_status_manager_get_order_status_posts')) {
            $wc_order_statuses = array_reduce(
                wc_order_status_manager_get_order_status_posts(),
                function ($result, $item) {
                    $result[$item->post_name] = $item->post_title;
                    return $result;
                },
                array()
            );

            return $wc_order_statuses;
        }

        if (function_exists('wc_get_order_statuses')) {
            $wc_get_order_statuses = wc_get_order_statuses();
            return $this->process_wc_statuses($wc_get_order_statuses);
        }

        $order_status_terms = get_terms('shop_order_status', 'hide_empty=0');

        $shop_order_statuses = array();
        if (!is_wp_error($order_status_terms)) {
            foreach ($order_status_terms as $term) {
                $shop_order_statuses[$term->slug] = $term->name;
            }
        }

        return $shop_order_statuses;
    }


    function process_wc_statuses($array)
    {
        $new_array = array();
        foreach ($array as $key => $value) {
            $new_array[substr($key, 3)] = $value;
        }

        return $new_array;
    }

}