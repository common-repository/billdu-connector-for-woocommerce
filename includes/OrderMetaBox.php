<?php

namespace MF;

use WC_Payment_Gateways;


class OrderMetaBox
{

    public $client;

    public function __construct()
    {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
    }

    function add_meta_boxes()
    {
        add_meta_box('wc_mf_invoice_box', __('MF Invoices', 'wc-billdu'), array($this, 'add_box'), 'shop_order', 'side');
    }

    function add_box($post)
    {
        $order = wc_get_order($post->ID);
        $invoiceId = get_post_meta($post->ID, 'mf_invoice_id', true);
        $invoiceLink = get_post_meta($post->ID, 'mf_invoice_link', true);
        $invoicePdf = get_post_meta($post->ID, 'mf_invoice_pdf', true);
        $proformaId = get_post_meta($post->ID, 'mf_proforma_id', true);
        $proformaLink = get_post_meta($post->ID, 'mf_proforma_link', true);
        $proformaPdf = get_post_meta($post->ID, 'mf_proforma_pdf', true);

        echo '<img src="https://my.billdu.com/images/logo_color_com.png">';
        echo '<h4 class="mf_document_title">Proforma:</h4>';
        if (!empty($proformaId)) {
            echo 'Proforma ID: ' . $proformaId;
            echo '<br><a href="' . $proformaLink . '" class="button" target="_blank">' . __('View Proforma', 'wc-billdu') . '</a>';
            echo ' <a href="' . $proformaPdf . '" class="button" target="_blank">' . __('Download PDF', 'wc-billdu') . '</a>';
        } else {
            echo '<p>Proforma will be generated when order status will be: <b>' . get_option('woocommerce_mf_document_proforma_' . $order->payment_method) . '</b>';
        }

        echo '<h4 class="mf_document_title">Invoice:</h4>';
        if (!empty($invoiceId)) {
            echo 'Invoice ID: ' . $invoiceId;
            echo '<br><a href="' . $invoiceLink . '" class="button" target="_blank">' . __('View Invoice', 'wc-billdu') . '</a>';
            echo ' <a href="' . $invoicePdf . '" class="button" target="_blank">' . __('Download PDF', 'wc-billdu') . '</a>';
        } else {
            echo '<p>Invoice will be generated when order status will be: <b>' . get_option('woocommerce_mf_document_invoice_' . $order->payment_method) . '</b>';
        }
    }

}