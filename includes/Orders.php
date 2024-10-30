<?php

namespace MF;

use WC_Payment_Gateways;
use iInvoices\Api\ApiClient;


class Orders
{

    public $client;

    public function __construct()
    {
        $endpoints = [
            'com' => 'https://api.billdu.com',
            'sk' => 'https://api.minifaktura.sk',
            'cz' => 'https://api.minifaktura.cz',
        ];

        $this->client = new ApiClient($endpoints[get_option('woocommerce_mf_endpoint')], get_option('woocommerce_mf_apikey'), get_option('woocommerce_mf_apisecret'));
        add_action('woocommerce_order_status_changed', array($this, 'order_status_changed'));
        add_action('woocommerce_checkout_order_processed', array($this, 'order_status_changed'));
    }


    function order_status_changed($order_id)
    {
        $order = wc_get_order($order_id);
        if (get_option('woocommerce_mf_document_invoice_' . $order->payment_method) === $order->status) {
            $this->generateDocument('invoice', $order);
        }

        if (get_option('woocommerce_mf_document_proforma_' . $order->payment_method) === $order->status) {
            $this->generateDocument('proforma', $order);
        }

        $invoiceId = get_post_meta($order->id, 'mf_invoice_id');

        if ((get_option('woocommerce_mf_add_payment') === $order->status) && $invoiceId) {
            $this->addPayment($invoiceId, $order);
        }
    }

    function addPayment($invoiceId, $order)
    {
        if (empty(get_post_meta($order->id, 'mf_payment_added'))) {
            $this->client->invoices->pay($invoiceId[0], (float)$order->total);
            update_post_meta($order->id, 'mf_payment_added', $invoiceId);
        }
    }

    function generateDocument($type = 'invoice', $order)
    {
        $documentId = get_post_meta($order->id, 'mf_' . $type . '_id');

        if (!empty($documentId)) {
            return;
        }

        $name = ($order->billing_company) ? $order->billing_company : $order->billing_first_name . ' ' . $order->billing_last_name;
        $client = [
            "company" => $name,
            "fullname" => $order->billing_first_name . ' ' . $order->billing_last_name,
            "street" => $order->billing_address_1,
            "street2" => $order->billing_address_2,
            "zip" => $order->billing_postcode,
            "city" => $order->billing_city,
            "country" => $order->billing_country,
            "shippingCompany" => $order->shipping_company,
            "shippingName" => $order->shipping_first_name,
            "shippingSurname" => $order->shipping_first_name,
            "shippingFullname" => $order->shipping_last_name . ' ' . $order->shipping_last_name,
            "shippingStreet" => $order->shipping_address_1,
            "shippingStreet2" => $order->shipping_address_2,
            "shippingZip" => $order->shipping_postcode,
            "shippingCity" => $order->shipping_city,
            "shippingProvince" => "",
            "shippingCountry" => $order->shipping_country,
            "phone" => $order->billing_phone,
            "mobil" => $order->billing_phone,
            "note" => "",
            "email" => $order->billing_email,
            "companyId" => $order->billing_company_wi_id,
            "vatId" => $order->billing_company_wi_vat,
            "taxId" => $order->billing_company_wi_tax
        ];

        $items = [];

        $order_items = $order->get_items();

        foreach ($order_items as $item_id => $item_data) {
            $product_name = $item_data['name'];
            $item_quantity = $order->get_item_meta($item_id, '_qty', true);

            $item_total = $order->get_item_meta($item_id, '_line_total', true);
            $item = [
                "label" => $product_name,
                "price" => $item_total / $item_quantity,
                "tax" => 0,
                "stockNumber" => "",
                "count" => $item_quantity,
            ];

            $product = new \WC_Product($item_data['product_id']);
            $item_meta = new \WC_Order_Item_Meta($item_data['item_meta']);

            $attributes = $item_meta->meta ? $item_meta->display(true, true, '_', ', ') : '';
            $non_variations_attributes = $this->get_non_variations_attributes($item['product_id']);
            $variation = $product instanceof \WC_Product_Variation ? $this->convert_to_plaintext($product->get_variation_description()) : '';
            $shortDescription = $this->convert_to_plaintext($product->get_post_data()->post_excerpt);
            $template = get_option('woocommerce_mf_item_name', $this->product_description_template_default);

            $item['label'] .= ' - ' . strtr($template, array(
                    '[ATTRIBUTES]' => $attributes,
                    '[NON_VARIATIONS_ATTRIBUTES]' => $non_variations_attributes,
                    '[VARIATION]' => $variation,
                    '[SHORT_DESCRIPTION]' => $shortDescription,
                    '[SKU]' => $product->get_sku()
                ));

            $items[] = $item;
        }

        $shipping = [
            "label" => get_option('woocommerce_mf_shipping_item_name'),
            "price" => $order->shipping_total,
            "tax" => $order->shipping_tax,
            "stockNumber" => "",
            "count" => 1,
        ];
        $items[] = $shipping;

        $clientResponse = $this->client->clients->create($client);


        $document = [
            "type" => "invoice",
            "client" => $clientResponse->id,
            "items" => $items,
            "payment" => "transfer",
            "deliveryType" => "",
            "currency" => "EUR",
            "discount" => $order->discount_total
        ];

        $portalUrls = [
            'sk' => 'https://moja.minifaktura.sk',
            'cz' => 'https://moje.minifaktura.cz',
            'com' => 'https://my.billdu.com'
        ];

        $documentResponse = $this->client->documents->create($document);

        if ($order->billing_email && get_option('woocommerce_mf_sent_email') === 'yes') {
            $mailData = [
                "subject" => get_option('woocommerce_mf_email_subject'),
                "message" => get_option('woocommerce_mf_email_message'),
                "recipients" => [
                    $order->billing_email,
                ]
            ];
            $this->client->documents->send($documentResponse->id, $mailData);
        }

        update_post_meta($order->id, 'mf_' . $type . '_id', $documentResponse->id);
        update_post_meta($order->id, 'mf_' . $type . '_pdf', $this->client->documents->getDownloadLink($documentResponse->id));
        update_post_meta($order->id, 'mf_' . $type . '_link', $portalUrls[get_option('woocommerce_mf_endpoint')] . '/company.documents.' . $type . 's.view/default/' . $documentResponse->id);
    }

    function get_non_variations_attributes($product_id)
    {
        $attributes = get_post_meta($product_id, '_product_attributes');
        if (!$attributes) {
            return false;
        }
        $result = [];
        foreach ($attributes[0] as $attribute) {
            if ($attribute['is_variation']) {
                continue;
            }

            $result[] = $attribute['name'] . ': ' . $attribute['value'];
        }

        return implode(', ', $result);
    }

    function convert_to_plaintext($string)
    {
        return html_entity_decode(wp_strip_all_tags($string), ENT_QUOTES, get_option('blog_charset'));
    }
}