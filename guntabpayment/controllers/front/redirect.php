<?php

class GuntabpaymentRedirectModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;
        $customer = new Customer($cart->id_customer);
        $apiKey = Configuration::get('GUNTAB_API_KEY');

        // Create the order
        $this->module->validateOrder(
            (int)$cart->id,
            Configuration::get('PS_OS_PREPARATION'),
            (float)$cart->getOrderTotal(true, Cart::BOTH),
            $this->module->displayName,
            null,
            [],
            (int)$cart->id_currency,
            false,
            $cart->secure_key
        );
		$order = new Order($this->module->currentOrder);

       // GunTab category map
// Mapping of PrestaShop category names to GunTab listing_type_id
		$categoryMap = [
			'Ammo' => 'ammunition_and_flammables',
			'Antique Guns' => 'antique_gun',
			'AOW' => 'aow',
			'C&R' => 'curio_relic',
			'Handgun' => 'handgun',
			'Handgun Frames' => 'handgun_frame',
			'Rifles' => 'long_gun',
			'Shotguns' => 'long_gun',
			'Receivers' => 'long_gun_receiver',
			'Lowers' => 'long_gun_receiver',
			'Machine Guns' => 'machine_gun',
			'Magazines' => 'magazine',
			'non-regulated' => 'other_non_regulated',
			'SBR & SBS' => 'short_barreled_long_gun',
			'Suppressors' => 'suppressor',
		];

		$products = $cart->getProducts();
		$listings = [];

		foreach ($products as $product) {
			// Get default category ID and load the category object
			$category = new Category($product['id_category_default'], $this->context->language->id);
			$categoryName = $category->name;

			// Resolve listing_type_id based on the mapping
			$listing_type_id = isset($categoryMap[$categoryName]) ? $categoryMap[$categoryName] : 'other_non_regulated';

			$listings[] = [
				'listing_type_id' => $listing_type_id,
				'quantity' => (int)$product['cart_quantity'],
				'title' => $product['name'],
				'url' => $this->context->link->getProductLink($product['id_product']),
			];
		}

        // Construct payload
        $payload = [
            'buyer_email' => $customer->email,
            'buyer_notification_method' => 'email',
            'manual_sales_tax_amount_cents' => (int)((($order->total_paid_tax_incl - $order->total_products_wt) > 1) 
				? (($order->total_paid_tax_incl - $order->total_products_wt) * 10)
				: (($order->total_paid_tax_incl - $order->total_products_wt) * 100)),
            'merchandise_amount_cents' => (int)($order->total_products_wt * 100),
            'payment_method_convenience_fee_paid_by' => 'buyer',
            'seller_order_id' => (string)$order->id,
            'redirect_url' => $this->context->link->getPageLink('order-detail', true, null, 'id_order=' . $order->id),
            'service_fee_paid_by' => 'seller',
            'shipping_amount_cents' => (int)($order->total_shipping * 100),
            'type' => 'Invoice',
            'workflow_state' => 'pending_counterparty_response',
            'listings' => $listings
        ];

        // Send API request
        $ch = curl_init('https://api.guntab.com/v1/invoices');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Token ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES));
        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        // Log debug info
        file_put_contents(_PS_ROOT_DIR_ . '/log-guntab.txt',
            "REDIRECT HOOK\ncURL error: $curl_error\n\nRAW Response:\n$response\n\nPARSED:\n" . print_r(json_decode($response, true), true)
        );

        $json = json_decode($response, true);
        if (isset($json['response_url'])) {
            Tools::redirect($json['response_url']);
        } else {
            $this->setTemplate('module:guntabpayment/views/templates/front/error.tpl');
        }
    }
}
