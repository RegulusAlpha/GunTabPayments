# GunTab Payment Gateway for PrestaShop BETA

This module enables secure and compliant payment processing via [GunTab](https://www.guntab.com) for online stores powered by PrestaShop.

🛠️ **Supports:**
- PrestaShop 1.7+
- Product-level category mapping to GunTab `listing_type_id`
- Dynamic invoice redirection to GunTab
- Admin-configurable service and convenience fee handling
- Invoice PDF notices
- Product-specific eligibility toggling (optional)

---

## 🚀 Features (BETA EXPERIMENTAL TESTING)

- ✅ Send product listings directly to GunTab with mapped `listing_type_id`
- ✅ Use GunTab for only specific products or categories
- ✅ Automatically redirects customer to GunTab to complete payment
- ✅ Configurable `service_fee_paid_by` and `payment_method_convenience_fee_paid_by`
- ✅ Automatically adds invoice PDF notice when GunTab is used
- ✅ Logs API responses to `log-guntab.txt` for debugging

---

## 📦 File Structure

```text
guntabpayment/
├── guntabpayment.php                       # Main module file (registration, settings, hooks)
├── controllers/
│   └── front/
│       └── redirect.php                    # Handles order creation and GunTab invoice API call
├── views/
│   ├── img/
│   │   └── guntab.png                      # GunTab logo for checkout display
│   └── templates/
│       ├── front/
│       │   └── error.tpl                   # Fallback error display template
│       └── hook/
│           └── payment_return.tpl         # Display after order confirmation
```
⚙️ Installation
- Upload the guntabpayment/ folder to your /modules/ directory.
- In the PrestaShop admin panel, go to Modules > Module Manager and install GunTab Payment Gateway.

Configure your:
- API Key from GunTab
- Service Fee Paid By (buyer/seller/split)
- Payment Method Convenience Fee Paid By (buyer/seller/split)

Optionally, transplant the displayPDFInvoice hook if it isn't hooked automatically.

🔐 Security
All API communication uses HTTPS and GunTab authentication tokens. Your API key is stored securely via PrestaShop's Configuration system.

📄 License
Open Software License (OSL 3.0)

🤝 Contributions
Feel free to submit issues, enhancements, or pull requests.

