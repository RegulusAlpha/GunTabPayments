<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class GunTabPayment extends PaymentModule
{
    const SERVICE_FEE_PAID_BY = 'GUNTAB_SERVICE_FEE_PAID_BY';
    const PAYMENT_METHOD_CONVENIENCE_FEE_PAID_BY = 'GUNTAB_PAYMENT_METHOD_CONVENIENCE_FEE_PAID_BY';

    public function __construct()
    {
        $this->name = 'guntabpayment';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.2';
        $this->author = 'regulusalpha';
        $this->need_instance = 0;
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        parent::__construct();

        $this->displayName = $this->l('GunTab Payment Gateway');
        $this->description = $this->l('Accept payments via GunTab.');
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('paymentReturn') &&
			$this->registerHook('displayPDFInvoice') &&
            $this->registerHook('displayHeader');
    }

    public function uninstall()
    {
        Configuration::deleteByName('GUNTAB_API_KEY');
        Configuration::deleteByName(self::SERVICE_FEE_PAID_BY);
        Configuration::deleteByName(self::PAYMENT_METHOD_CONVENIENCE_FEE_PAID_BY);
        return parent::uninstall();
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submit_' . $this->name)) {
            Configuration::updateValue('GUNTAB_API_KEY', Tools::getValue('GUNTAB_API_KEY'));
            Configuration::updateValue(self::SERVICE_FEE_PAID_BY, Tools::getValue(self::SERVICE_FEE_PAID_BY));
            Configuration::updateValue(self::PAYMENT_METHOD_CONVENIENCE_FEE_PAID_BY, Tools::getValue(self::PAYMENT_METHOD_CONVENIENCE_FEE_PAID_BY));
            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }

        return $output . $this->renderForm();
    }

    public function renderForm()
    {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('GunTab API Key'),
                        'name' => 'GUNTAB_API_KEY',
                        'required' => true
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Service Fee Paid By'),
                        'name' => self::SERVICE_FEE_PAID_BY,
                        'required' => true,
                        'options' => [
                            'query' => [
                                ['id' => 'buyer', 'name' => 'Buyer'],
                                ['id' => 'seller', 'name' => 'Seller'],
                                ['id' => 'split', 'name' => 'Split'],
                            ],
                            'id' => 'id',
                            'name' => 'name'
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Payment Method Convenience Fee Paid By'),
                        'name' => self::PAYMENT_METHOD_CONVENIENCE_FEE_PAID_BY,
                        'required' => true,
                        'options' => [
                            'query' => [
                                ['id' => 'buyer', 'name' => 'Buyer'],
                                ['id' => 'seller', 'name' => 'Seller'],
                                ['id' => 'split', 'name' => 'Split'],
                            ],
                            'id' => 'id',
                            'name' => 'name'
                        ],
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ]
            ]
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        $helper->fields_value = $this->getConfigFormValues();

        return $helper->generateForm([$fields_form]);
    }

    public function getConfigFormValues()
    {
        return [
            'GUNTAB_API_KEY' => Configuration::get('GUNTAB_API_KEY', ''),
            self::SERVICE_FEE_PAID_BY => Configuration::get(self::SERVICE_FEE_PAID_BY, 'seller'),
            self::PAYMENT_METHOD_CONVENIENCE_FEE_PAID_BY => Configuration::get(self::PAYMENT_METHOD_CONVENIENCE_FEE_PAID_BY, 'buyer'),
        ];
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return [];
        }

        $option = new PaymentOption();
        $option->setCallToActionText($this->l('Pay with '))
            ->setAction($this->context->link->getModuleLink($this->name, 'redirect', [], true))
            ->setLogo($this->_path . 'views/img/guntab.png');

        return [$option];
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return '';
        }

        return $this->fetch('module:' . $this->name . '/views/templates/hook/payment_return.tpl');
    }
	
	public function hookDisplayPDFInvoice($params)
{
    /** @var OrderInvoice $invoice */
    $invoice = $params['object'];

    // Get the order and check if it used GunTab
    $order = new Order((int)$invoice->id_order);
    if ($order->module !== $this->name) {
        return '';
    }

    return '
	    <table style="margin-top:20px;" cellpadding="4" cellspacing="0" width="100%">
        <tr>
            <td style="border:1px solid #000000; padding:10px; background-color:#f5f5f5;">
                <strong>GunTab Payment Notice:</strong><br />
                This order was placed using GunTab. The buyer was redirected to GunTab to complete payment and also received an email with payment instructions.<br /><br />
                If invoice is not accepted in <strong>1 day</strong> and payment is not completed within <strong>3 days</strong>, the order will be cancelled automatically by GunTab.
            </td>
        </tr>
    </table>';
	
}


}