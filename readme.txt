=== Multibanco, MB WAY, Credit card, Apple Pay, Google Pay, Payshop, Cofidis Pay, and PIX (ifthenpay) for WooCommerce ===
Contributors: nakedcatplugins, webdados, ifthenpay
Tags: ifthenpay, ecommerce, portugal, atm, homebanking
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.2
Stable tag: 11.3.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Secure WooCommerce payments with Multibanco, MB WAY, Credit card, Apple Pay, Google Pay, Payshop, Cofidis, and PIX via ifthenpay’s payment gateway.

== Description ==

“Pagamento de Serviços” (payment of services) on Multibanco (Portuguese ATM network), and MB WAY (using the customer’s mobile phone number), are the most popular ways to pay for services and (online) purchases in Portugal.
Portuguese consumers trust the “Multibanco” and “MB WAY” payment methods more than any other.

This plugin generates a “Multibanco” Payment Reference that customers can use to pay for their WooCommerce orders at an ATM or via home banking, or an “MB WAY” payment request which will send a push notification to the customer’s mobile phone for payment approval.

Credit or debit cards, including Apple Pay and Google Pay, CTT Payshop, and Cofidis Pay payment methods are also available.

Holders of a Brazilian bank account can conveniently pay for WooCommerce orders in Euros, with automatic currency conversion to Brazilian Real, using PIX.

This is the official [ifthenpay](https://ifthenpay.com) plugin, and a contract with this company is required. Technical support is provided by [Naked Cat Plugins](https://nakedcatplugins.com) (by [Webdados](https://www.webdados.pt)) on the [WordPress.org support forums](https://wordpress.org/support/plugin/multibanco-ifthen-software-gateway-for-woocommerce/).

**Fully compatible with the new [High-Performance Order Storage](https://woocommerce.com/posts/platform-update-high-performance-order-storage-for-woocommerce/) (HPOS) and the [WooCommerce block-based checkout](https://woocommerce.com/checkout-blocks/).**

= Features: =

* Generates a Multibanco Reference for simple payment on the Portuguese ATM network or home banking service;
* Allows the customer to pay using MB WAY using their mobile phone;
* Allows the customer to pay using their Credit or debit card, including Apple Pay and Google Pay;
* Generates a Payshop Reference for simple payment on the [Payshop agents network](https://www.payshop.pt/fepsapl/app/open/showSearchAgent.jspx), CTT stores or post offices available all over Portugal;
* Allows the customer to pay in up to 12 interest-free installments via Cofidis Pay;
* Customers with Brazilian bank accounts can use PIX;
* Multibanco references with expiration date if the “MB Key” configuration method is used;
* Automatically changes the order status to “Processing” (or “Completed” if the order only contains virtual downloadable products) and notifies both the customer and the store owner if the automatic “Callback” upon payment is activated;
* Automatic “Callback” can be activated upon request to ifthenpay, via the plugin settings screen for each payment method;
* Refunds for MB WAY and Credit or debit card - [read this](https://helpdesk.ifthenpay.com/pt-PT/support/solutions/articles/79000130517-devoluc%C3%B5es-de-pagamentos-aos-ordenantes);
* Shop owner can set minimum and maximum order totals for each payment gateway to be available;
* Ability to reduce stock when the order is created or paid;
* Allows searching orders (in the admin area) by Multibanco or Payshop reference;
* High-Performance Order Storage (HPOS) compatible;
* Block-Based Checkout compatible;
* WPML compatible (for multilingual shops);
* Polylang tested;
* [WooCommerce Subscriptions](https://woocommerce.com/products/woocommerce-subscriptions/) integration (only Multibanco);
* [WooCommerce Deposits by webtomizer](https://woocommerce-deposits.com/) integration (except Cofidis Pay);
* Integration for 3rd party SMS notification plugins (only Multibanco and Payshop):
	* [WooCommerce - APG SMS Notifications](https://wordpress.org/plugins/woocommerce-apg-sms-notifications/);
	* [Twilio SMS Notifications](https://woocommerce.com/products/twilio-sms-notifications/);
	* [YITH WooCommerce SMS Notification](https://yithemes.com/themes/plugins/yith-woocommerce-sms-notifications/);
	* [E-goi SMS Orders Alert/Notifications for WooCommerce](https://pt.wordpress.org/plugins/sms-orders-alertnotifications-for-woocommerce/) (integration implemented on their plugin)
	* Other providers can be added upon request (under quotation);

= PRO add-on =

Get the [PRO add-on](https://nakedcatplugins.com/product/multibanco-mbway-credit-card-payshop-ifthenpay-woocommerce-pro-add-on/) and unlock extra features:

* Recover unpaid MB WAY orders by converting them to Multibanco and notifying the customer via email;
* Automatic cancellation of orders when Multibanco, Payshop, or MB WAY references expire;
* Countdown timer on the “Thank you” page for MB WAY payments;
* Save the MB WAY mobile number to the user profile for future purchases;
* Trigger Multibanco and MB WAY payments when creating orders via the REST API;
* Store and show the ifthenpay fee on each order;
* Codifis payment information banner, with the price to pay monthly, on the product page (simple and variable products);
* Set a payment entity for Multibanco, MB WAY, Credit card, Payshop, and Cofidis Pay at the product category level, so that you can receive payment in different accounts based on the order products (replaces previously available specific extension);
	* Simplified marketplace;
	* Lock the cart so you can only have products from a single account in the same order;
* Remove “ifthenpay Gateway” from the payment method title on the frontend for Apple Pay, Google Pay, and PIX
* Remove payment instructions from specific emails, for example, “order completed”, to avoid customer confusion when they are no longer necessary;
* Autofill the VAT number on the Cofidis Pay gateway from several VAT number plugins (more can be added on request);
* Change the default timeout for calls to the ifthenpay APIs if your website is experiencing communication difficulties;
* Hide notices of new available payment methods;
* Hide notices of pending callback activation requests;
* Hide sidebar in payment method settings screen;
* More features coming soon;
* By purchasing the PRO add-on, you get the following:
	* All the features described above;
	* Premium technical support (conditions apply);
	* Contribution to the continued development of the solution;
	* Automatic updates;
	* Good karma;

The PRO add-on is a product of [Naked Cat Plugins](https://nakedcatplugins.com) (by [Webdados](https://www.webdados.pt)), and is not provided by ifthenpay.

= Other (premium) plugins =

Already know our other WooCommerce (premium) plugins?

* [Portuguese Postcodes for WooCommerce](https://nakedcatplugins.com/product/portuguese-postcodes-for-woocommerce-technical-support/) - Automatic filling of the address details at the checkout, including street name and neighbourhood, based on the postal code
* [Invoicing with InvoiceXpress for WooCommerce](https://invoicewoo.com/) - Automatically issue invoices directly from the WooCommerce order
* [DPD Portugal for WooCommerce](https://nakedcatplugins.com/product/dpd-portugal-for-woocommerce/) - Create shipping and return guide in the DPD API directly from the WooCommerce order
* [Feed KuantoKusta for WooCommerce](https://nakedcatplugins.com/product/feed-kuantokusta-for-woocommerce-pro/) - Publish your products on Kuanto Kusta with this easy to use feed generator
* [Simple Checkout Fields Manager for WooCommerce](https://nakedcatplugins.com/product/simple-custom-fields-for-woocommerce-blocks-checkout/) - Add custom fields and manage (remove, make required or optional) core fields on the new WooCommerce Block-based Checkout
* [Simple WooCommerce Order Approval](https://nakedcatplugins.com/product/simple-woocommerce-order-approval/) - The hassle-free solution for WooCommerce order approval before payment
* [Shop as Client for WooCommerce](https://nakedcatplugins.com/product/shop-as-client-for-woocommerce-pro-add-on/) - Quickly create orders on behalf of your customers
* [Taxonomy/Term and Role based Discounts for WooCommerce](https://nakedcatplugins.com/product/taxonomy-term-and-role-based-discounts-for-woocommerce-pro-add-on/) - Easily create bulk discount rules for products based on any taxonomy terms (built-in or custom)
* [DPD / SEUR / Geopost Pickup and Lockers network for WooCommerce](https://nakedcatplugins.com/product/dpd-seur-geopost-pickup-and-lockers-network-for-woocommerce/) - Deliver your WooCommerce orders on the DPD and SEUR Pickup network of Parcelshops and Lockers in 21 European countries

== Installation ==

* Make sure you already have a contract with [ifthenpay](https://ifthenpay.com)
* Use the included automatic install feature on your WordPress admin panel and search for “ifthenpay”.
* Multibanco: Go to WooCommerce > Settings > Checkout > Pagamento de Serviços no Multibanco and fill in the data provided by ifthenpay: MB Key (or legacy Entity and Subentity).
* MB WAY: Go to WooCommerce > Settings > Checkout > Pagamento MB WAY no telemóvel and fill in the data provided by ifthenpay: MB WAY Key.
* Credit or debit card: Go to WooCommerce > Settings > Checkout > Credit or debit card and fill in the data provided by ifthenpay: Credit card Key.
* Payshop: Go to WooCommerce > Settings > Checkout > Pagamento na rede de agentes Payshop and fill in the data provided by ifthenpay: Payshop Key.
* Cofidis Pay: Go to WooCommerce > Settings > Checkout > Cofidis Pay and fill in the data provided by ifthenpay: Cofidis Pay Key.
* Apple Pay, Google Pay, and PIX: Go to WooCommerce > Settings > Checkout > ifthenpay Gateway and fill in the data provided by ifthenpay: Backoffice Key, then choose the Gateway Key (request a new “Static gateway” if you don’t have one already), and finally, the Payment methods you want to activate.
* Make sure you ask ifthenpay to activate the “Callback” on their side with the URL and Anti-phishing key provided in the settings screen. There is a feature on each payment method screen that allows you to make this request via an API, except for payment methods via the “ifthenpay Gateway” (Apple Pay, Google Pay, and PIX), where the callback activation is automatically set.
* Start receiving payments :-)

== Frequently Asked Questions ==

= Can I start receiving payments right away? Show me the money! =

You have to sign a contract with ifthenpay to activate this service. Go to [ifthenpay.com](https://ifthenpay.com/?lang=en) for more information and to sign up.

= I’m an individual and not a registered business. Can I use this plugin? =

ifthenpay only provides this service to registered businesses and equivalents (such as tax-registered freelancers).
You should [contact ifthenpay](https://ifthenpay.com/#contactos) if you need additional details on this matter.

= The generated Multibanco reference does not contain the order number. How do I know which order was paid? =

ifthenpay will send you an email each time a reference is paid, but the reference does not contain the order number. By design, our plugin does not include the order number in the reference because of the way WooCommerce/WordPress sets orders/posts IDs.
Anyway, you do not need the order number in the reference, because our plugin uses a callback mechanism. ifthenpay automatically notifies WooCommerce when a specific Multibanco reference is used for payment. The order is linked to the reference in the database and will also be automatically set as paid.
If you still need to know which order a reference is linked to, use the search box on the WooCommerce Orders administration screen.

= How do I test the callback call to simulate a payment and foresee what happens when a real payment is made by a customer? =

Edit your wp-config.php file and set `WP_DEBUG` to `true`.
Then, place a test order in the frontend using any of the plugin’s payment methods.
Go to the order edit screen and click the “Simulate callback payment” button.
Don’t forget to set `WP_DEBUG` to `false` again, as it should not be active on a production website.

= How to issue an MB WAY or Credit or debit card refund within WooCommerce? =

Just like in any other WooCommerce payment gateway that supports refunds.
Check out the instructions carefully [here (Automatic refunds, step 4)](https://woocommerce.com/document/woocommerce-refunds/) and [here (WooCommerce specific instructions)](https://helpdesk.ifthenpay.com/pt-PT/support/solutions/articles/79000130517-devoluc%C3%B5es-de-pagamentos-aos-ordenantes).

= Can I use this plugin and the ifthenpay service on more than one website? =

Yes, but not with the same payment method keys.
Ask ifthenpay for different credentials for each website, and payment method, you need the service to be available.
There are no extra costs, and you can even route payments to separate bank accounts.

= Can I change the look and feel of the payment instructions on the “Thank you” page and/or the new order email, as well as the SMS message format? =

Yes, you can, but you should know your way around WordPress filters.
There are filters for this, and you can find examples in `hooks-examples.php`.

= Can I change the payment gateway icon on the checkout page? =

There are also filters for this. See `hooks-examples.php`.

= I want to charge an additional fee for these payment methods. How should I do it? =

You shouldn’t! To our knowledge, it’s illegal under [Portuguese law](https://www.bportugal.pt/sites/default/files/anexos/legislacoes/dl3ano2010.PDF) and a [European directive](https://europa.eu/youreurope/business/finance-funding/making-receiving-payments/electronic-cash-payments/index_en.htm) to charge an extra fee based on the payment method chosen by the customer.
If you don’t care about legislation, plugins are available that allow you to set extra fees per payment method. Please, don’t ask us for support on this.

= How much time does the customer have to pay with MB WAY? =

The MB WAY payment requests expire after 4 minutes.

= Why doesn’t the customer receive an email when paying with MB WAY, Credit card, Apple Pay, Google Pay, or PIX? =

These payments need to be approved immediately after checking out.
WooCommerce only sends payment instruction emails for payment methods that require later payment, not immediate payment, like Multibanco, Payshop, and Bank transfer, for example.
The customer will still receive an email upon payment if properly configured in WooCommerce (just not before).

= Do Apple Pay and Google Pay support Express Checkout? =

Not at this time. Apple Pay and Google Pay are processed via the ifthenpay Gateway. Everything related to payment happens there, and the customer is redirected back to the website afterward.
This means the checkout addresses are the ones the user entered on the website checkout page, not the ones provided by Apple or Google, which also means any custom field will be collected, which doesn’t happen on Express Checkout.

= [WPML] My website is multilingual. Will I be able to use this plugin? =

Yes. This plugin is officially WPML compatible.
You will need the WPML and WPML String Translation Plugins (alongside WooCommerce Multilingual, which is mandatory for any WooCommerce + WPML install). 

= [WPML] How can I translate the payment method title and description the customer sees on the checkout page to secondary languages? =

Go to WPML > String Translation > Search and translate all the strings in the `woocommerce` and `multibanco_ifthen_for_woocommerce` domains containing `ifthen_for_woocommerce` in their name.

= [SMS] How to include the Multibanco and/or Payshop payment instructions in the SMS sent by “WooCommerce - APG SMS Notifications”? =

Go to WooCommerce > SMS Notifications and add the `%multibanco_ifthen%` and/or `%payshop_ifthen%` variables to “Order on-hold custom message”.

= [SMS] How to include the Multibanco and/or Payshop payment instructions in the SMS sent by “Twilio SMS Notifications”? =

Go to WooCommerce > Settings > SMS and add the `%multibanco_ifthen%` and/or `%payshop_ifthen%` variables to “Customer Notifications“ > “On Hold SMS Message”.

= [SMS] How to include the Multibanco and/or Payshop payment instructions in the SMS sent by “YITH WooCommerce SMS Notification”? =

Go to YITH Plugins > SMS Notifications > SMS Settings and add the `{multibanco_ifthen}` and/or `{payshop_ifthen}` variables to “On hold”.

= [Advanced] Can I use a specific Multibanco Entity/Subentity or Key, MB WAY, Credit card, or Payshop Key based on order details? =

Yes, you should use the `multibanco_ifthen_base_ent_subent` or `multibanco_ifthen_base_mbkey`, `multibanco_ifthen_base_mbwaykey`, `multibanco_ifthen_base_creditcardkey` or `multibanco_ifthen_base_payshopkey` filters. See `hooks-examples.php`.

= [Advanced] The order is set to “On Hold” for Multibanco and Payshop, can I make it “Pending” by default? =

You shouldn’t but… yes, you can. Just return `false` to the `multibanco_ifthen_set_on_hold` and/or `payshop_ifthen_set_on_hold` filter.
Be advised that no “new order” email, with payment instructions, will be sent to the customer unless you use some plugin or custom code to force it.

= [Advanced] I’ve set up WooCommerce to cancel unpaid orders after x minutes, why are my Multibanco and/or Payshop orders not being canceled? =

WooCommerce only automatically cancels “Pending” orders, not “On Hold”, because these orders are set to be paid by offline methods (like Multibanco or Payshop), and payment can occur at any time, even after the order is canceled. Still, if you want to take that risk, just return `true` to the `multibanco_ifthen_cancel_unpaid_orders` and/or `payshop_ifthen_cancel_unpaid_orders` filter.
You can also restore that order’s product stock by returning `true` to the `multibanco_ifthen_cancel_unpaid_orders_restore_stock` and/or `payshop_ifthen_cancel_unpaid_orders_restore_stock` filter, although the WooCommerce team [doesn’t recommend](https://github.com/woocommerce/woocommerce/issues/3712#issuecomment-23650563) it.
Be advised the Multibanco or Payshop reference will still be active and can be paid later on.

= [Advanced] Can I prevent the plugin from adding the payment instructions and/or the payment received message to emails? =

You can use the `multibanco_ifthen_email_instructions_pending_send` and/or `mbway_ifthen_email_instructions_pending_send` filter: return false and the payment instructions won’t be included in the “new order” email – we do not recommend you to do it, though.
You can use the `multibanco_ifthen_email_instructions_payment_received_send` and/or `mbway_ifthen_email_instructions_payment_received_send` filter: return false and the payment received message won’t be included in the “Processing” or “Completed” email.

= ifthenpay says my callback URL is returning a 404 error. Is there a solution? =

You probably have weird permalink settings (or permalinks not set at all) in your WordPress install.

If your permalinks are set as “Plain”, tell them to change the callback URL from `https://yourwebsite/wc-api/WC_Multibanco_IfThen_Webdados/?chave=[CHAVE_ANTI_PHISHING]...` to `https://yourwebsite/?wc-api=WC_Multibanco_IfThen_Webdados&chave=[CHAVE_ANTI_PHISHING]...`.
If your permalinks are set as “Custom structure”: `/index.php/%postname%/` tell them to set the callback to `https://yourwebsite/index.php/wc-api/WC_Multibanco_IfThen_Webdados/?chave=[CHAVE_ANTI_PHISHING]...`

= Is this plugin compliant with the European Union General Data Protection Regulation (GDPR)? =

This plugin does not collect or send any private data of the website where it’s installed, its customers, or the orders, to Webdados (the plugin author) or ifthenpay (the payment processor).
In the MB WAY module, the mobile phone number is collected to request payment authorization and it can be legitimately processed based on Article 6 (1) (b) of the GDPR.
ifthenpay’s privacy policy can be found at [https://ifthenpay.com/termosCondicoes](https://ifthenpay.com/termosCondicoes)

= Is this plugin compatible with the new WooCommerce High-Performance Order Storage? =

Yes. This plugin is fully compatible with HPOS since December 2022.

= Is this plugin compatible with the WooCommerce Cart and Checkout Blocks? =

Yes. This plugin is compatible with the Block-Based Checkout since August 2020.

= I need technical support. Who should I contact, ifthenpay or Webdados? =

Although this is the official ifthenpay WooCommerce plugin, development and support is provided by [Webdados](https://www.webdados.pt).
For free/standard support you should use the [support forums here at WordPress.org](https://wordpress.org/support/plugin/multibanco-ifthen-software-gateway-for-woocommerce/)
For premium, urgent and experimental integrations support or custom developments you should contact [Webdados](https://www.webdados.pt/contactos/). Charges will apply.

Any support related to failed payments or credit card authorizations should be directed to [ifthenpay](https://ifthenpay.com/#contactos).

= Where do I report security vulnerabilities found in this plugin? =  
 
You can report any security bugs found in the source code of this plugin through the [Patchstack Vulnerability Disclosure Program](https://patchstack.com/database/vdp/multibanco-ifthen-software-gateway-for-woocommerce). The Patchstack team will assist you with verification, CVE assignment and take care of notifying the developers of this plugin.

= Can I contribute with a translation? =

Sure. Go to [GlotPress](https://translate.wordpress.org/projects/wp-plugins/multibanco-ifthen-software-gateway-for-woocommerce) and help us out.

== Changelog ==

= 11.3.2 - 2026-01-21 =
* [FIX] Add an empty function to the `wc_ifthen_hourly_cron` scheduled action to avoid loop issues on Action Scheduler, introduced on 11.3.0, and clear its failed logs (Thanks BestSites.pt for reporting)
* [DEV] Move global upgrade routines from the Multibanco to the main plugin class

= 11.3.1 - 2026-01-15 =
* [DEV] Remove unnecessary development folders from the repository

= 11.3.0 - 2026-01-14 =
* [DEV] New `mbway_ifthen_checkout_phone_field_max_width_break_fields` filter to override the container maximum width after which the MB WAY classic checkout fields country code and phone number show up in separate lines, instead of side by side. Default: `400px`
* [DEV] Replace hourly WP cron with Action Scheduler
* [TWEAK] Normalize MB WAY payment date/time received on the callback to ISO format
* [FIX] Callback URL output on the callback activation section on each payment method
* [DEV] Tested with 7.0-alpha-61480 and WooCommerce 10.4.3

= 11.2.1 - 2025-10-30 =
* [FIX] Debug errors to email when requesting Multibanco or MB Way payments to ifthenpay
* [TWEAK] Small readme.txt adjustments
* [TWEAK] Replace “homebanking” with “home banking”
* [DEV] Tested with WordPress 6.9-beta2-61087 and WooCommerce 10.3.3

= 11.2.0 - 2025-10-20 =
* [TWEAK] Display payment method icons on the backend
* [TWEAK] Better UX on the country code and phone number fields on the blocks checkout
* [TWEAK] Remove old method of callback activation via email
* [FIX] Display correct translated payment gateway title and description on the blocks checkout if WPML is active
* [DEV] Tested with WordPress 6.9-alpha-60939 and WooCommerce 10.3.0-rc.1

= 11.1.0 - 2025-09-22 =
* [DEV] Change some remaining `wc_get_orders` calls to our internal wrapper
* [DEV] Use WooCommerce internal method to get international calling codes for MB WAY numbers
* [DEV] Fix version upgrade routine
* [DEV] Remove autoload from some options
* [DEV] Tested with WordPress 6.9-alpha-60789 and WooCommerce 10.2.1

= 11.0.0 - 2025-09-11 =
* [NEW] It’s now possible to use international phone numbers for MB WAY (can be disabled on the payment method options)
* [DEV] Use ifthenpay’s MB WAY most recent API endpoint
* [DEV] Compare values with two decimal places when checking for a possible order value change
* [DEV] Tested with WordPress 6.9-alpha-60725 and WooCommerce 10.2.0-beta.2

= 10.5.0 - 2025-08-19 =
* [TWEAK] New payment method notices are now only shown every 180 days for each admin or shop manager user, and this is now handled by user meta and not a transient, because in some setups because transients are cleared when the cache is cleared
* [FIX] Store MB KEY on the order meta when saving the Multibanco payment details
* [DEV] Debug (extra) time it took on each API call
* [DEV] Tested with WordPress 6.9-alpha-60645 and WooCommerce 10.1.0

= 10.4.1 - 2025-05-22 =
* [FIX] Check for WooCommerce version before declaring HPOS and Blocks checkout compatibility
* [DEV] Tested with WordPress 6.9-alpha-60243 and WooCommerce 9.9.0-beta.1

= 10.4.0 - 2025-05-03 =
* [TWEAK] New payment method notices are now only shown every 90 days for each admin or shop manager user (they can also be dismissed by returning `true` to the `multibanco_ifthen_hide_newmethod_notifications` filter)

= 10.3.0 - 2025-04-12 =
* [NEW] We are now called Naked Cat Plugins 😻
* [DEV] Update Multibanco API URL
* [DEV] Update Cofidis API URL
* [DEV] New `mbway_ifthen_after_process_payment` hook (action)
* [TWEAK] [PRO add-on 5.1](https://nakedcatplugins.com/product/multibanco-mbway-credit-card-payshop-ifthenpay-woocommerce-pro-add-on/): Only save MBWAY number to user profile if the request payment API call to ifthenpay is successfully
* [DEV] Tested with WordPress 6.8-RC3-60146 and WooCommerce 9.8.1

= 10.2.1 - 2025-03-17 =
* [DEV] Stop trying to fix the email locale with WPML active because it was causing the language to be incorrect (can be reactivated by returning `true` to `multibanco_ifthen_maybe_change_email_locale` filter)
* [TWEAK] Reuse several i18n strings on all the payment methods with placeholders
* [DEV] Tested with WordPress 6.8-beta2-59993 and WooCommerce 9.8.0-beta.1

= 10.1.0 - 2025-01-15 =
* [FIX] Callback calls failing for values under 1.00 Euro
* [FIX] Set Payshop expiration at the end of the day
* [DEV] Tested with WordPress 6.8-alpha-59604 and WooCommerce 9.6.0-rc.1

= 10.0.2 - 2025-01-13 =
* [FIX] PHP notice on the `multibanco_ifthen_thankyou_instructions_table_html` filter

= 10.0.1 - 2025-01-07 =
* [FIX] New payment method admin notices not showing properly

= 10.0.0 - 2025-01-06 =
* [NEW] Apple Pay, Google Pay, and PIX payment methods (via ifthenpay Gateway)
* [NEW] Plugin name: Multibanco, MB WAY, Credit card, Apple Pay, Google Pay, Payshop, Cofidis Pay, and PIX (ifthenpay) for WooCommerce
* [NEW] Credit card callback as a fallback in case something fails when the customer returns from the gateway
* [NEW] Developer filters to manipulate the payment method data for each method on the blocks checkout, for [example to change the icon](https://gist.github.com/webdados/6d9808d3c8c099f4a84d4b8eec69dc66)
* [TWEAK] Also reload the “Thank you” page when the MB WAY payment has expired
* [TWEAK] Better cache busting when reloading the “Thank you” page
* [TWEAK] Resize the payment methods banner on the order admin page
* [TWEAK] Replace “IfthenPay” with “ifthenpay” for branding coherence
* [TWEAK] Better information for refunds not issued
* [TWEAK] Several updates to the readme.txt
* [FIX] Make sure all the values are sent to the ifthenpay API with “.” as a decimal separator, even in bizarre PHP setups
* [FIX] Hide settings fields on each method when the required keys are not set
* [DEV] New `refund_ifthen_api_timeout` developer filter
* [DEV] Apply additional WordPress Coding Standards
* [DEV] Requires PHP 7.2, WordPress 5.8, and WooCommerce 7.1
* [DEV] Tested with WordPress 6.8-alpha-59576 and WooCommerce 9.6.0-beta.1

= 9.7.1 - 2024-11-29 =
* [FIX] Fix MB WAY and Cofidis order status check on the “Thank you” for non-logged in customers

= 9.7 - 2024-11-26 =
* [TWEAK] Change MB WAY timeout from 5 to 4 minutes (which is the real interval set by SIBS)
* [TWEAK] Better “refund not issued” message
* [FIX] Check order object before saving Multibanco details on the classic checkout
* [DEV] Change default webservices timeout to 15 seconds instead of 30
* [DEV] Tested with WordPress 6.8-alpha-59459 and WooCommerce 9.5.0-beta.1

= 9.6.0 - 2024-10-08 =
* [FIX] Load text domain at the right time to avoid PHP notices on WordPress 6.7 and above
* [DEV] Tested with WordPress 6.7-beta1-59184 and WooCommerce 9.4.0-beta.2

= 9.5.0 - 2024-09-19 =
* [FIX] Get the Multibanco reference earlier on the blocks checkout so that we can throw the error before hitting the “Thank you” page
* [TWEAK] Try to get MB WAY phone number from `$_REQUEST` if not found in `$_POST` for edge scenarios
* [TWEAK] Refactor code to set initial order status, order note and payment request errors
* [TWEAK] Update readme.txt FAQ information about 404 callback error because of specific permalink settings
* [DEV] Tested with WordPress 6.7-alpha-59064 and WooCommerce 9.3.2

= 9.4.4 - 2024-07-16 =
* [TWEAK] Implement hook on the MB WAY payment gateway needed by [PRO add-on 4.1](https://nakedcatplugins.com/product/multibanco-mbway-credit-card-payshop-ifthenpay-woocommerce-pro-add-on/)
* [DEV] Tested with WordPress 6.7-alpha-58848 and WooCommerce 9.2.0-beta.1

= 9.4.3 - 2024-07-16 =
* [TWEAK] Implement hook on the MB WAY settings screen needed by the [PRO add-on 4.0](https://nakedcatplugins.com/product/multibanco-mbway-credit-card-payshop-ifthenpay-woocommerce-pro-add-on/)
* [DEV] Tested with WordPress 6.6-RC4-58723 and WooCommerce 9.1.2

= 9.4.2 - 2024-06-27 =
* [TWEAK] Add the order as second argument for the `creditcard_ifthen_cancel_order_on_back` filter

= 9.4.1 - 2024-06-26 =
* [TWEAK] The order is now not canceled when hitting “Back” on the credit card gateway, and the user is redirected to the checkout. The old behavior can be activated by returning `true` to `creditcard_ifthen_cancel_order_on_back`.
* [TWEAK] Change Cofidis Pay “payment received” text to better explain the payment was pre-approved and that the shop owner still needs to submit documentation to Cofidis before processing the order.
* [DEV] Tested with WordPress 6.6-RC1-58573 and WooCommerce 9.1.0-beta.1

= 9.4.0 - 2024-06-05 =
* [FIX] Error notice on the block-based checkout when returning from the Cofidis Pay gateway without success
* [TWEAK] Remove .pot file from the repository
* [TWEAK] Include source files for payment gateway blocks
* [DEV] Tested with WordPress 6.6-beta1	and WooCommerce 9.0.0-rc.1

= 9.3.0 - 2024-05-22 =
* [NEW] `ifthen_allow_settings_woocommerce_not_euro` filter to allow setting up the payment gateways even if the shop currency is not set to Euro - For multi-currency shops
* [FIX] Deprecation notices “Creation of dynamic property” on PHP 8.2 and above
* [FIX] Cofidis Pay return without Success attribute on the callback URL
* [TWEAK] Refactor the way the payment gateways are loaded so that plugins that call new \WC_Payment_Gateways(); will get our payment methods
* [TWEAK] Cofidis Pay icon in SVG format
* [DEV] Tested with WordPress 6.6-alpha-58182 and WooCommerce 8.9.1

= 9.2.4 - 2024-04-09 =
* [TWEAK] Show [PRO add-on 3.5](https://nakedcatplugins.com/product/multibanco-mbway-credit-card-payshop-ifthenpay-woocommerce-pro-add-on/) blurred options 

= 9.2.3 - 2024-04-08 =
* [NEW] [PRO add-on 3.5](https://nakedcatplugins.com/product/multibanco-mbway-credit-card-payshop-ifthenpay-woocommerce-pro-add-on/): Codifis payment information banner, with the price to pay month, on the product page (simple product for now)
* [DEV] Tested with WordPress 6.6-alpha-57928 and WooCommerce 8.8.0-rc.1

= 9.2.2 - 2024-03-26 =
* [FIX] Deprecation notices “Creation of dynamic property” on PHP 8.2 and above
* [FIX] Typo
* [DEV] Tested with WordPress 6.5-RC3-57875 and WooCommerce 8.7.0

= 9.2.0 - 2024-03-18 =
* [FIX] Avoid loading payment gateways more than once
* [FIX] Typo on the Cofidis Pay settings
* [DEV] Set WC_IFTHENPAY_WEBDADOS_PLUGIN_FILE for usage on the Pro Add-on
* [DEV] Set `Requires Plugins` tag to `woocommerce`
* [DEV] Tested with WordPress 6.5-RC2-57846 and WooCommerce 8.7.0-rc.1

= 9.1.2 - 2024-03-03 =
* [DEV] Store Mutibanco Key used to generate reference
* [DEV] Return Multibanco Key and requestId on the `get_multibanco_order_details()` method
* [NEW] [PRO add-on 3.3](https://nakedcatplugins.com/product/multibanco-mbway-credit-card-payshop-ifthenpay-woocommerce-pro-add-on/): Get ifthenpay fee on order screen if Backoffice Key is set and fee not yet set from the callback

= 9.1.1 - 2024-03-03 =
* [DEV] Allow filtering backoffice key

= 9.1.0 - 2024-03-02 =
* [NEW] [PRO add-on 3.2](https://nakedcatplugins.com/product/multibanco-mbway-credit-card-payshop-ifthenpay-woocommerce-pro-add-on/): Store and show ifthenpay fees on order (Multibanco, MB WAY and Payshop)
* [DEV] Allow filtering callback URLs
* [DEV] New `order_has_ifthenpay_method` method
* [DEV] Tested with WordPress 6.5-beta3-57747 and WooCommerce 8.7.0-beta.2

= 9.0.1 - 2024-02-17 =
* [FIX] Deprecated notices for `wc_get_log_file_path` for WooCommerce 8.6 and above
* [FIX] Fatal error when installing the plugin in HPOS mode
* [DEV] Tested with WordPress 6.5-alpha-57571 and WooCommerce 8.6

= 9.0.0 - 2024-01-17 =
* New payment method: Cofidis Pay - Pay in up to 12 interest-free installments
* Better quality payment method banners on emails
* Apply additional WordPress Coding Standards
* Requires WordPress 5.6 and WooCommerce 6.0
* Tested with WordPress 6.5-alpha-57258 and WooCommerce 8.5

= 8.9.3 - 2023-12-12 =
* You can safely update to this version if you’re running WooCommerce 5.0 or newer **but we’ll very drop support for WooCommerce previous to 6.0 on the next update**
* Declare WooCommerce block-based Cart and Checkout compatibility
* Fix jQuery deprecation warning on the Multibanco settings screen

= 8.9.2 - 2023-12-07 =
* [PRO add-on 2.0](https://nakedcatplugins.com/product/multibanco-mbway-credit-card-payshop-ifthenpay-woocommerce-pro-add-on/): Trigger Multibanco and MB WAY payments when creating orders via the REST API

= 8.9.1 - 2023-12-07 =
* Fix Credit Card Callback testing when WP_DEBUG = true
* Better debug on the new `wc_get_orders` wrapper
* Apply additional WordPress Coding Standards
* Tested with WordPress 6.5-alpha-57159 and WooCommerce 8.4.0-rc.1

= 8.9.0 - 2023-12-05 =
* `wc_get_orders` wrapper to remove Polylang language filters when seraching for orders, for example on callback calls, and apply meta conversions for HPOS in the wrapper instead of all over the place
* Better explanation of value limits on each gateway
* Fix credit card API refund URL from HTTP to HTTPS
* Start applying WordPress Coding Standards
* Tested with WordPress 6.5-alpha-57150 and WooCommerce 8.4.0-beta.1

= 8.8.0 - 2023-11-15 =
* [PRO add-on 1.6](https://nakedcatplugins.com/product/multibanco-mbway-credit-card-payshop-ifthenpay-woocommerce-pro-add-on/): Countdown timer on the “Thank you” page for MB WAY payments
* Tested with WordPress 6.5-alpha-57110, WooCommerce 8.3.0-rc.2 and WooCommerce Blocks 11.5.4

= 8.7.0 - 2023-10-30 =
* Remove beta status from the HPOS and Blocks Checkout compatibility
* Tested with WordPress 6.5-alpha-57027, WooCommerce 8.2.1 and WooCommerce Blocks 11.4.1

= 8.6.0 - 2023-08-31 =
* Pass $_GET to the `*_ifthen_callback_payment_complete` hooks
* Update hooks-examples.php with an example on how to use the payment complete hook with the new parameter
* Tested with WordPress 6.4-beta2-56771 and WooCommerce 8.2.0-rc.2

= 8.5.0 - 2023-08-31 =
* Fix a PHP notice
* Rearrange premium plugins information on the settings screen
* Tested with WordPress 6.4-alpha-56479 and WooCommerce 8.1.0-beta.1

= 8.4.0 - 2023-08-04 =
* Better compatibility with newer versions of WooCommerce Deposits by webtomizer (Thanks Instituto Macrobiótico de Portugal)
* Throw Exception instead of adding notice when finalizing the order, to be compatible with both traditional and blocks checkout
* Tested with WordPress 6.3-RC3-56344 and WooCommerce 8.0.0-rc.1

= 8.3.0 - 2023-07-08 =
* Fix a small bug when showing the order value on our metabox on the order edit screen on multicurrency websites
* Tested with WordPress 6.3-beta3-56143 and WooCommerce 7.9.0-rc.2

= 8.2.0 - 2023-04-28 =
* Fix a bug when changing email language if WPML is active
* Add security bugs report information to the readme file
* Tested with WordPress 6.3-alpha-55693 and WooCommerce 7.7.0-beta.2

= 8.1.0 - 2023-04-04 =
* [PRO add-on 1.3](https://nakedcatplugins.com/product/multibanco-mbway-credit-card-payshop-ifthenpay-woocommerce-pro-add-on/): Recover unpaid MB WAY orders by converting them to Multibanco and notify the customer via email
* Fix a bug where old installs would incorrectly show the “MB Key or Entity and subentity” setting on the backoffice
* Remove debug string from MB WAY settings
* Add FAQ about why the MB WAY payment instructions are not send by email
* Tested with PHP 8.1.9, WordPress 6.3-alpha-55618 and WooCommerce 7.6.0-beta.2

= 8.0.2 - 2023-04-01 =
* Fix a PHP notice
* Tested with WordPress 6.3-alpha-55615 and WooCommerce 7.6.0-beta.2

= 8.0.1 - 2023-02-28 =
* New actions before `process_payment` functions
* Tested with WordPress 6.2-beta3-55428 and WooCommerce 7.5.0-beta.2

= 8.0.0 - 2023-02-03 =
* You can safely update to this version if you’re running WooCommerce 5.0 or newer
* Support for the new WooCommerce block based checkout (in beta) for all payment methods;
* Tested with WordPress 6.2-alpha-55198, WooCommerce 7.4.0-beta.2 and WooCommerce Blocks 9.5.0

= 7.1.1 - 2022-12-18 =
* Set debug to true by default on new installs
* Fix the callback instructions when using the new MB Key instead of Entity / Subentity
* Fix version number on 7.1.0
* Tested with WordPress 6.2-alpha-54951 and WooCommerce 7.2.0

= 7.0.0 - 2022-12-05 =
* Direct and automatic MB WAY and Credit or debit card refunds via the order admin screen
* High-Performance Order Storage compatible (in beta and only on WooCommerce 7.1 and above)
* Fix a bug on emails when the shop language is not the same as the user managing the orders
* Fix jQuery deprecations
* Requires WooCommerce 5.0
* Tested with WordPress 6.2-alpha-54888 and WooCommerce 7.2.0-beta.1

= 6.5.2 - 2022-11-29 =
* You can safely update to this version if you’re running WooCommerce 4.3 or newer **but we’ll very soon drop support for WooCommerce previous to 5.0**
* Fix trailing comma that was causing a fatal error on PHP below 7.3

= 6.5.1 - 2022-11-11 =
* Requires WooCommerce 4.3
* Removed MB WAY support for WooCommerce Subscriptions because the customer only have 5 minutes to pay for a renewal he might not be expecting
* Fix id stored in Payshop references when order number is being used instead of order id
* Fix a bug on the MB WAY callback introduced in 6.5.0
* Code cleanup
* Declare WooCommerce High-Performance Order Storage incompatibility (for now)
* Tested with WordPress 6.2-alpha-54799 and WooCommerce 7.1.0

= 6.5.0 - 2022-11-11 =
* Do not use this version

= 6.4.1 - 2022-11-03 =
* Fix bug on MB WAY callback when comparing the incoming reference with the order id and/or number which would cause the order not to be identified
* Debug tweaks - Stop sending “payment received” emails and only send warning or error emails
* Tested with WordPress 6.1 and WooCommerce 7.1.0-rc.1

= 6.4.0 - 2022-10-25 =
* [PRO add-on 1.1](https://nakedcatplugins.com/product/multibanco-mbway-credit-card-payshop-ifthenpay-woocommerce-pro-add-on/): allow sending order number (for sequential order number plugins) instead of order id to the ifthenpay webservices and backoffice
* Tested with WordPress 6.1-RC2-54684 and WooCommerce 7.1.0-beta.1

= 6.3.0 - 2022-10-19 =
* **New [PRO add-on](https://nakedcatplugins.com/product/multibanco-mbway-credit-card-payshop-ifthenpay-woocommerce-pro-add-on/)**
* Code refactoring when querying orders, cancel expiring orders
* Suggest MB Key instead of Entity / Subentity
* Replace “home banking” with “homebanking”
* Tested with WordPress 6.1-beta3-54428 and WooCommerce 7.1.0-beta.1

= 6.2.0 - 2022-09-03 =
* Fix - Payment instructions were not shown on subscription parent and renewal orders
* Tested with WordPress 6.1-alpha-54043 and WooCommerce 6.9.0-beta.2

= 6.1.1 - 2022-08-01 =
* Restore - The option to reduce stock when the order is created is available again

= 6.1.0 - 2022-07-28 =
* **Dev - Removed support for WooCommerce below 4.0 (launched in March 2020)**
* **Dev - Removed support for WordPress below 5.0 (launched in December 2018)**
* **Dev - Removed support for PHP below 7.0 (launched in December 2015)**
* Remove - The option to reduce stock when the order is created is no longer available as it worked only for WooCommerce below 3.4.0
* Fix - Set order as paid when order total is 0 and bypass any payments
* Fix - [https://wordpress.org/support/topic/exemplo-para-filtro-multibanco_ifthen_webservice_expire_days/](The `multibanco_ifthen_webservice_expire_days` filter was not working)
* Requires WordPress 5.0, WooCommerce 4.0 and PHP 7.0

= 6.0.3 - 2022-06-30 =
* Fix - Multibanco would not be available on checkout when the new “MB Key” configuration method is active in some scenarios
* Fix - Check if the checkout block is installed on the page instead of only checking if we are on the checkout page

= 6.0.2 - 2022-06-27 =
* Remove “WC-” from the order ID sent to the new Multibanco “MB Key” API, to be more coherent to what we do in MB WAY

= 6.0.1 - 2022-06-24 =
* Fix a bug on the callback activation request with the new “MB Key” configuration method (do not request for callback activation on 6.0.0, intall 6.0.1 and then do it)
* Fix a PHP warning
* Under the hood: Change the way the plugin version is called

= 6.0.0 - 2022-06-24 =
* New configuration method with an “MB Key” instead of an Entity and Subentity, which uses an API and allows for reference expiration (you should ask ifthenpay for configuration details for this method)
* New filters for the new configuration method: `multibanco_ifthen_base_mbkey`, `multibanco_ifthen_webservice_timeout`, `multibanco_ifthen_webservice_desc` and `multibanco_ifthen_webservice_expire_days`
* Tested with WordPress 6.1-alpha-53556 and WooCommerce 6.7.0-beta.1

= 5.2.0 - 2022-05-31 =
* New brand: PT Woo Plugins 🥳
* See you in WordCamp Europe 2022, in Porto?
* Tested with WordPress 6.1-alpha-53451 and WooCommerce 6.6.0-rc.1

= 5.1.4 - 2022-05-04 =
* May the 4th be with you
* Small change on the checkout MB WAY field label
* Tested with WordPress 6.0-beta2-53236 and WooCommerce 6.5.0-rc.1

= 5.1.3 - 2022-01-05 =
* New option to set Payshop reference validity to 15 days
* Small bug fixes on checking the payment methods key length
* Tested with WordPress 5.9-RC1-52446 and WooCommerce 6.1.0-rc.2
* Happy New Year!

= 5.1.2 - 2021-08-11 =
* It’s now possible to remove the new methods notifications by returning `true` to the `multibanco_ifthen_hide_newmethod_notifications` filter
* Tested with 5.9-alpha-51588 and WooCommerce 5.6.0-rc.1

= 5.1.1 - 2021-06-16 =
* Fix a bug on the credit card gateway where some payments were not recognised
* Small tweaks and debug
* Tested with WordPress 5.8-beta2-51167 and WooCommerce 5.4.1

= 5.1.0 - 2021-05-27 =
* Several code tweaks, input sanitization and extra checks
* Tested with WordPress 5.8-alpha-51034, WooCommerce 5.4.0-rc.1 and WooCommerce Blocks 5.2.0

= 5.0.1 - 2021-04-03 =
* New `multibanco_ifthen_send_email_instructions`, `mbway_ifthen_send_email_instructions`, `creditcard_ifthen_send_email_instructions` and `payshop_ifthen_send_email_instructions` filters to allow removing the payment gateway instructions from emails
* Fix Credit card settings fields not hiding when the key is not set
* WooCommerce Blocks (4.7.0 and above) improvements: respect the Multibanco “Only for Portuguese customers” setting and fix icon size
* Small copy adjustments
* Tested with WordPress 5.8-alpha-50650, WooCommerce 5.2.0-rc.1 and WooCommerce Blocks 4.7.0

= 5.0.0 - 2021-03-16 =
* **New payment method available: “Credit or debit card”** (WooCommerce >= 4.0) - You need to sign an [amendment to the contract](https://www.ifthenpay.com/downloads/ifmb/AditamentoCCredito.pdf)
* **Requires WordPress 4.6 and WooCommerce 3.0 or above**
* **For WooCommerce 2.6 support you need to use [version 4.4.9](https://downloads.wordpress.org/plugin/multibanco-ifthen-software-gateway-for-woocommerce.4.4.9.zip)**
* Plugin name changed
* New and faster MB WAY endpoint
* Force “New order” email to the store owner upon Payshop payment
* Remove non-small icons
* Renamed our order metabox to “ifthenpay” for simplicity
* Simplification of the way we check if WooCommerce is active
* Do not change the Multibanco reference when paying again from the customer area in “Incremental references with expiration date” mode and the reference is not expired yet
* New filters to allow repositioning of the payment instructions on emails: `multibanco_ifthen_email_hook`, `multibanco_ifthen_email_hook_priority`, `mbway_ifthen_email_hook`, `mbway_ifthen_email_hook_priority`, `creditcard_ifthen_email_hook`, `creditcard_ifthen_email_hook_priority`, `payshop_ifthen_email_hook` and `payshop_ifthen_email_hook_priority`
* New `mbway_ifthen_pay_another_method_button_text` to be able to change the “choose another method button text” on MB WAY
* Bugfix: check for the “Completed” status on mbway.js, in addition to “Processing”
* Bugfix: PHP notice on Multibanco “Incremental references with expiration date” mode
* Bugfix: When paying again from the customer area, the old reference was being sent on the email in Multibanco “Incremental references with expiration date” mode
* Bugfix: When paying again from the customer area, no email was sent if the customer changes from Multibanco to Multibanco in “Incremental references with expiration date” mode and the reference is already expired
* Full readme.txt and hooks-examples.php revision
* Several small improvements
* Tested with WordPress 5.8-alpha-50535 and WooCommerce 5.1.0

= 4.4.9 - 2021-02-19 =
* You can safely update this plugin if you’re running WooCommece 2.6.0 or newer **but we’ll drop support for WooCommerce previous to 3.0 IN THE NEXT RELEASE**
* Last release before 5.0 (good news are on its way)
* Fix force “New order” email to the store owner upon Multibanco payment on WooCommerce 5.0 and above
* Tested with WordPress 5.7-beta2-50285 and WooCommerce 5.1.0-beta.1

= 4.4.8 - 2020-12-22 =
* Fix minimum and maximum values for all gateways
* Fix PHP notice
* Tested with WordPress 5.7-alpha-49862 and WooCommerce 4.9.0-beta.1

= 4.4.7 - 2020-12-10 =
* You can safely update this plugin if you’re running WooCommece 2.6.0 or newer **but we’ll drop support for WooCommerce previous to 3.0 IN THE NEXT RELEASE**
* Requires WordPress 4.4 or above
* Small readme.txt fix
* Tested with WordPress 5.7-alpha-49782 and WooCommerce 4.8

= 4.4.6 - 2020-11-04 =
* Bugfix setting the Multibanco order cancelation when using references with expiration (Thanks @josefreitas2)
* Lay ground for a (yet to be confirmed) MB WAY refund functionality - Callback processing
* Tested with WordPress 5.6-beta1-49314 and WooCommerce 4.7.0-rc.1

= 4.4.5 - 2020-08-11 =
* Bugfix when sending order emails after a Payshop order is paid for
* Tested with WordPress 5.5-RC3-48781, WooCommerce 4.4.0-rc.1 and WooCommerce Blocks 3.1.0

= 4.4.4 - 2020-08-05 =
* New WooCommerce Blocks checkout only if the feature plugin is installed and activated
* Bugfix on the Payshop callback activation request
* Fix a PHP notice
* Tested with WordPress 5.5-RC1-48708, WooCommerce 4.4.0-rc.1 and WooCommerce Blocks 3.1.0


= 4.4.3 - 2020-07-23 =
* Revert showing the Multibanco “payment received” message on order completed emails (introduced on 4.4.0)
* Only show WooCommerce Subscriptions options if the plugin is active
* Only enable Multibanco support for the WooCommerce Blocks checkout if WooCommerce Blocks version is 3.0.0. or above and the support is enabled via the payment method options

= 4.4.2 =
* Bugfix when WooCommerce Blocks 3.0.0 or above is active
* Try to fix a fatal error when themes override the WooCommerce email templates with old (pre WooCommerce 2.6.0) versions
* Tested with WordPress 5.5-beta3-48556, WooCommerce 4.3.1 and WooCommerce Blocks 3.0.0

= 4.4.1 =
* Bugfix checking if order is paid when the “WooCommerce Order Status Manager” (by SkyVerge) plugin is active

= 4.4.0 =
* New `ifthen_unpaid_statuses` filter to allow developers to set additional valid “unpaid” statuses for Multibanco, MB WAY and Payshop orders, besides the default ones (“on-hold”, “pending” and “partially-paid”). The statuses are used for callback validation, SMS message template, show order as unpaid on the backoffice, show “Pay” button on My Account orders list, issue new references if order value changes on the backoffice, reduce order stock rules, “Thank you” page and email payment instructions.
* Enforce requirement of WooCommerce 2.6.0 or above and bumped the `WC requires at least` tag accordingly
* Enforce requirement of WordPress 4.4 or above and bumped the `Requires at least` tag accordingly
* New filter `ifthen_debug_log_extra` that will allow developers to further debug the Multibanco reference generation (for now)
* Try to fix a (very odd) behavior where the customer is redirected to the “pay order” page when completing checkout, which will generate a duplicate Multibanco payment reference (as expected).
* New filters to hide the “Pay” button on “My Account” (which we do not recommend): `multibanco_ifthen_hide_my_account_pay_button`, `mbway_ifthen_hide_my_account_pay_button`, `payshop_ifthen_hide_my_account_pay_button`
* (VERY) Experimental Multibanco support for the new [https://woocommerce.wordpress.com/category/blocks/](WooCommerce Blocks) [https://woocommerce.wordpress.com/2020/05/27/available-for-testing-a-block-based-woocommerce-cart-and-checkout/](checkout experience)
* Tested with WordPress 5.5-beta2-48501 and WooCommerce 4.3.0

= 4.3.0 =
* New instant callback activation method via webservice instead of email
* When checking if the customer is from Portugal (to show/hide the payment methods) we now also check the shipping country
* Only apply our WooCommerce 4.2.0 fix if version is equal or above 4.2.0 and below 4.3.0 (a WooCommerce fix is scheduled to be released on that version)
* More prominent admin notice regarding old WordPress, WooCommerce or PHP versions in use
* Remove old Spanish translation from the plugin folder, now that we have a proper one on WordPress.org thanks to [https://profiles.wordpress.org/fernandot](@fernandot)
* Tested with WordPress 5.5-alpha-48241 and WooCommerce 4.3.0-rc.2

= 4.2.3 =
* Show paid date and time on the order admin metabox
* Better handling when the customer decides to change payment method in “My Account”
* Only apply our WooCommerce 4.2.0 fix if “Prices entered with tax” is set to “Yes”
* Code refactoring to prepare the next phase of supporting only WooCommerce 3.0 and above

= 4.2.2 =
* **Temporarily, while [this WooCommerce bug](https://github.com/woocommerce/woocommerce/issues/26582) is not solved, the value will not be matched when checking the callback and no new Multibanco or Payshop reference will be issued if the order changes value**
* Clarification on the settings page that the same set of entities or keys should never be used in more than one platform
* Links to the Payshop agents and CTT stores search on the Payshop method extra instructions default message
* Fix MB WAY phone number field hidden on some themes
* Fix MB WAY and Payshop key fields appearance on the payment method settings
* Show the Pay button on My Account for Multibanco and Payshop “On hold” orders
* Better information when MB WAY order is already paid for
* Better debug when requesting the MB WAY payment to the ifthenpay webservice
* readme.txt tweaks
* Tested with WordPress 5.5-alpha-47923 and WooCommerce 4.2.0

= 4.2.1 =
* Bugfix issuing new Multibanco or Payshop payment details when the order value is changed on wp-admin on WooCommerce 4.0 and above
* Extensions and other premium plugins list on the payment gateways settings page
* Tested with WordPress 5.5-alpha-47547 and WooCommerce 4.0.1

= 4.2.0 =
* Experimental: Automatically cancel unpaid orders after the Multibanco reference expires, if the “Incremental references with expiration date” mode is active
* Bugfix when hiding Multibanco settings fields, if the “Incremental references with expiration date” mode is active
* New hourly cron event for general plugin use
* Tested with WooCommerce 4.0.0-rc.1
* Requires WooCommerce 2.6.0 or above
* **For WooCommerce 2.5.0 support you need to use [version 4.1.3](https://downloads.wordpress.org/plugin/multibanco-ifthen-software-gateway-for-woocommerce.4.1.3.zip)**

= 4.1.3 =
* Fix bug on the subscription order edit screen

= 4.1.2 =
* Requires WordPress 4.1 or above
* Requires WooCommerce 2.5.0 or above
* Requires PHP 5.6 or above
* **For WordPress 4.0, WooCommerce 2.4.0 and PHP 5.5 support you need to use [version 4.1.1.2](https://downloads.wordpress.org/plugin/multibanco-ifthen-software-gateway-for-woocommerce.4.1.1.2.zip)**

= 4.1.1.2 =
* Remove other plugins publicity from the settings page

= 4.1.1.1 =
* Admin notice regarding old WordPress, WooCommerce or PHP versions in use (if you like to live in danger, you may disable it by returning `false` to the `ifthen_show_old_techonology_notice` filter)
* Better readme.txt information regarding updates
* Fix version number

= 4.1.0 =
* This is the first release aimed at bringing the plugin, and it’s users, to recent technology (many more will follow)
* Requires WordPress 4.0 or above
* Requires WooCommerce 2.4 or above
* Requires PHP 5.5 or above
* **For WordPress 3.8 and WooCommerce 2.2 support you need to use [version 4.0.8](https://downloads.wordpress.org/plugin/multibanco-ifthen-software-gateway-for-woocommerce.4.0.8.zip)**
* Use SVG icons and banners (except on emails where we still use PNG because SVG is not fully supported)
* Deprecate big icons on the checkout
* Tested with WordPress 5.3.3-alpha-46995 and WooCommerce 3.9.0-rc.2


= 4.0.8 =
* Fix WooCommerce Subscriptions experimental integration
* Tested with WooCommerce 3.8.1

= 4.0.7 =
* Fix Payshop small icon size
* Small fix on MB WAY WooCommerce Subscriptions support
* Tested with WordPress 5.3.1-alpha-46771

= 4.0.6 =
* Tested with WordPress 5.2.5-alpha and WooCommerce 3.8.0

= 4.0.5 =
* Fix fatal error on WooCommerce below 3.4.0 when MB WAY or Payshop were not initialized yet

= 4.0.4 =
* Fix bug when setting the Multibanco SMS instructions
* Improve WooCommerce Deposits by webtomizer compatibility

= 4.0.3 =
* Deactivate the payment methods if the required settings are not filled in
* Fix bug when showing the MB WAY expiration date on the order admin page
* Fix bug on the Payshop intructions on the “Thank you” page when the reference has no expiration date
* Correctly disable payment gateways if value is not on the allowed interval when payment inside the My account page
* readme.txt adjustments

= 4.0.2 =
* Fix version number

= 4.0.1 =
* Fix small icons by default
* Move mbway.js to the new assets folder and fix scripts version number
* Fix small bug on Payshop that allowed the anti-phishing key to be changed via the settings page after it was set
* Better MB WAY payment request debug

= 4.0.0 =
* Payshop support (WooCommerce >= 3.0)
* Plugin name change
* Enforce payment gateways minimum and maximum default values
* Change dumb quotes to smart quotes
* Add payment gateway logo to settings page and order metabox
* Admin CSS and JS as external assets instead of inline
* Check for order currency instead of global WooCommerce currency when the order already exists
* Several minor bugfixes and minimal code refactoring

= 3.6.4 =
* Bugfix when creating a new reference if the order value changes while editing it on wp-admin
* Tested with WordPress 5.2.3-alpha-45666 and WooCommerce 3.7.0-rc.1

= 3.6.3.1 =
* Bugfix on the WooCommerce Subscriptions integration (Thanks @vascothemudo)
* Tested with WooCommerce 3.6.2

= 3.6.3 =
* Add the `partially-paid` status to Multibanco valid pending payment status when checking the callback
* New `multibanco_ifthen_valid_callback_pending_status` and `mbway_ifthen_valid_callback_pending_status` filters when checking for pending payment orders on Multibanco and MB WAY callbacks (WooCommerce >= 3.0)
* Tested with WordPress 5.1.1 and WooCommerce 3.6.0-rc.1

= 3.6.2.2 =
* 30 seconds timeout instead of 10 seconds when calling ifthenpay’s MB WAY webservice, because SIBS is having performance problems which results in ifthenpay being unable to reply to our request on time

= 3.6.2.1 =
* Check for WooCommerce below 2.2 (apparently it’s still around) and stop the plugin initialization if found

= 3.6.2 =
* 10 seconds timeout instead of 5 seconds when calling ifthenpay’s MB WAY webservice
* New `mbway_ifthen_webservice_timeout` filter
* Callback verification fallback in the case the webservice times out but the MB WAY payment request is sent and paid anyway
* Small copy fixes

= 3.6.1 =
* Fix callback activation request broken by WooCommerce 3.5.5
* Better feedback if the callback activation email cannot be sent
* readme.txt improvements

= 3.6.0.1 =
* Changing Payment Methods support for WooCommerce Subscriptions (Thanks @ptravassos)

= 3.6 =
* Support for Multibanco references with expiration date (needs activation by ifthenpay)
* Payment instructions tables code refactoring
* Expiration date/time on the payment instructions tables (when applicable)
* Better workflow when requesting a new MB WAY payment, namely the possibility to change the phone number
* Minor bugfix on the MB WAY payment instructions
* Filterable interval on mbway.js

= 3.5 =
* Allow the customer to change payment method from the “Thank you” and “View order” (My account) page for orders with MB WAY as the payment method
* Allow shop owner to request MB WAY payment again after 6 minutes of the original request (instead of the previous 15 minutes)
* Change the payment instructions table on the “View order” (My account) page after the MB WAY payment request is expired and the order is still not paid (also a new `mbway_ifthen_thankyou_instructions_table_html_expired` filter)
* Small debug tweaks on mbway.js
* Minor tweaks on the plugin and readme.txt copy
* Tested with WordPress 5.1 and WooCommerce 3.5.5

= 3.4.3 =
* New option (activated by default) to force the resending of the “New Order” email (not the British Synthpop band), when the Multibanco payment is done via callback (this was happening erroneously before we fixed the stock management issue on 3.4.2, but we understand this is usefull for the Multibanco payment method)

= 3.4.2 =
* Fix stock management when it’s set to decrease on order
* New (experimental) feature: Request MB WAY payment again, on the order edit screen, available 15 minutes after the original request

= 3.4.1 =
* Small tweak on mbway.js
* Small tweak on the MB WAY payment instructions
* Dropped support for WooCommerce prior to 2.2 / Bumped `WC requires at least` tag
* Tested with WooCommerce 3.5.2 / Bumped `WC tested up to` tag
* Tested with WordPress 5.0 / Bumped `Tested up to` tag

= 3.4 =
* WooCommerce Deposits by webtomizer (experimental) integration on WooCommerce >= 3.0 (sponsored by mojobrands.net)
* Bugfix: MB WAY details not showing up on wp-admin
* Bugfix: Reduce stock correctly according to settings since [WooCommerce changed it’s behavior in 3.4.0](https://github.com/woocommerce/woocommerce/commit/70c9cff608761fcd48b57f709059e00b1ffeee38#diff-27a48ce67fa604181c90b4bb464164ac)

= 3.3.1 =
* Improved the `multibanco_ifthen_thankyou_instructions_table_html`, `multibanco_ifthen_set_on_hold`, `multibanco_ifthen_email_instructions_pending_send`, `multibanco_ifthen_email_instructions_table_html`, `multibanco_ifthen_sms_instructions`, `multibanco_ifthen_email_instructions_payment_received_send`, `multibanco_ifthen_email_instructions_payment_received`, `mbway_ifthen_thankyou_instructions_table_html`, `mbway_ifthen_enable_check_order_status_thankyou`, `mbway_ifthen_email_instructions_pending_send`, `mbway_ifthen_email_instructions_table_html`, `mbway_ifthen_email_instructions_payment_received_send`, `mbway_ifthen_email_instructions_payment_received`, `multibanco_ifthen_cancel_unpaid_orders_restore_stock` and `mbway_ifthen_cancel_unpaid_orders_restore_stock` filters by passing the order id to them
* Renamed `filters_examples.php` to `hooks-examples.php` and improved it with examples for all the plugin hooks

= 3.3 =
* New actions for developers: `multibanco_ifthen_created_reference`, `mbway_ifthen_created_reference`, `multibanco_ifthen_unpaid_order_cancelled`, `mbway_ifthen_unpaid_order_cancelled`, `multibanco_ifthen_callback_payment_complete`, `multibanco_ifthen_callback_payment_failed`, `mbway_ifthen_callback_payment_complete` and `mbway_ifthen_callback_payment_failed`
* Tested with WordPress * / Bumped `Tested up to` tag
* Downgraded the `Requires at least` tag to reflect the fact the plugin is still compatible with WooCommerce 2.0 and above
* Tested with WooCommerce 3.5.1 / Bumped `WC tested up to` tag
* Added `WC requires at least` tag on the plugin main file

= 3.2.1.2 =
* Fix when getting Order WPML language on WooCommerce below 3.0
* Tested with WooCommerce 3.5 / Bumped `WC tested up to` tag

= 3.2.1.1 =
* Fixed a small bug where the Multibanco payment details would be regenerated if, for some exotic reason, an order value was changed on wp-admin for already paid orders

= 3.2.1 =
* New `multibanco_ifthen_multibanco_settings_fields` and `multibanco_ifthen_mbway_settings_fields` filters to allow 3rd party plugins to add fields to the Multibanco and MB WAY settings screen
* `index.php` file because “best practices”
* Small coding standards fixes

= 3.2 =
* New behavior for special entities that don’t allow repeated payments in a specific time frame (only for WooCommerce 3.0 and above)
* New `multibanco_ifthen_base_mbwaykey` filter to be able to change the base MB WAY Key used to generate the payment details, based on the order, which may be useful for marketplaces
* Bumped `WC tested up to` tag

= 3.1.2 =
* Fix on the Twilio SMS integration (Thanks iOutlet)

= 3.1.1.2 =
* SVN mess-up fix

= 3.1.1 =
* GDPR chit-chat on the FAQ
* We no longer store the mobile phone number used for MB WAY

= 3.1 =
* Complete grammar and spelling review
* MB WAY description limited to 70 characters (Site name #order_id)
* Bumped `WC tested up to` tag

= 3.0.6 =
* Fixed a fatal error bug if the order is not found when the MB WAY callback is invoked by ifthenpay
* Better feedback to the customer, informing that there are only 5 minutes to complete the payment – because it seems SIBS has changed the timeout and told no one about it  ¯\_(ツ)_/¯

= 3.0.5 =
* Better WooCommerce detection
* Always round MB WAY values to two decimals
* Clean problematic characters from the MB WAY payment description
* Small fixes
* Bumped `WC tested up to` tag

= 3.0.4 =
* Debug when contacting the ifthenpay webservice to create the MB WAY payment request (shame on us...)
* Better feedback to the customer, informing that there are only 15 minutes to complete the payment
* Bumped `WC tested up to` tag

= 3.0.3 =
* Better (and persistent) feedback related to the callback still not being asked to ifthenpay
* Removed the `mbway_ifthen_set_on_hold` filter that no longer makes sense since 3.0.2

= 3.0.2 =
* Changed the default MB WAY order status to “pending”, because there’s a time limit to pay for the order. Orders will be automatically canceled if you use the “Manage stock” and “Hold stock” settings on WooCommerce. You can use the “on-hold” behavior like in Multibanco if you return false to `mbway_ifthen_order_initial_status_pending`. (Thanks for the mentoring @chrislema)
* Fix: Multibanco logo was not showing up on the email notifications after 3.0
* Fix: MB WAY Callback testing when WP_DEBUG = true
* Fix: Checking for “pending” order status if applicable
* Enhancement: hide callback and anti-phishing key if the settings were still not saved at least one time

= 3.0.1 =
* Fix: Fatal error for Polylang using WPML compatibility

= 3.0 =
* MB WAY support
* Code refactoring
* New retina ready and small icons
* Several fixes

= 2.1.4 =
* Fixed a bug where on WooCommerce < 3.0 references would be re-used incorrectly
* Re-enabled the use of references when they’re not used anymore on on-hold or pending orders

= 2.1.3 =
* Small change for compatibility with the new “WC – APG SMS Notifications” timer for on-hold status messages functionality
* Bumped `WC tested up to` tag

= 2.1.2.1 =
* Fix stable tag

= 2.1.2 =
* Fix: Some code introduced in version 2.1 was only compatible with WooCommerce 2.6 and above and on minor versions a fatal error was thrown

= 2.1.1.1 =
* Tested with WooCommerce 3.3

= 2.1.1 =
* New `multibanco_ifthen_cancel_unpaid_orders_restore_stock` filter to which `true` should be returned if you want the stock for the products on auto-cancelled orders (by the `multibanco_ifthen_cancel_unpaid_orders` filter) to be restored;
* FAQ improved;

= 2.1 =
* WooCommerce Subscriptions (experimental) integration on WooCommerce >= 3.0: Automatically sets renewal orders to be paid by Multibanco and generates the new payment details;
* Fix: Deletes payment details from orders that no longer have Multibanco as the payment method;
* Do not show payment instructions in the “Thank you” page unless the order is on hold or pending (edge cases);
* Show payment instructions in the order details screen on “My Account”; 

= 2.0.4.1 =
* Version fix

= 2.0.4 =
* [YITH WooCommerce SMS Notification](https://yithemes.com/themes/plugins/yith-woocommerce-sms-notifications/) plugin integration: it’s now possible to add Multibanco payment details to the SMS message sent by this plugin by using the {multibanco_ifthen} variable on the message template

= 2.0.3 =
* New `multibanco_ifthen_email_instructions_pending_send` filter to which you can return false so that the payment instructions are not included in the “new order” email, although we do not recommend doing it
* New `multibanco_ifthen_email_instructions_payment_received_send` filter to which you can return false so that the payment received message is not included in the “processing” email
* Bumped `Tested up to` tag 

= 2.0.2 =
* Database abstraction on WooCommerce 3.0 and above, by using `wc_get_orders`
* Small adjustments

= 2.0.1 =
* New `multibanco_ifthen_set_on_hold` filter to be able to leave the order pending instead of on hold by returning `false` - use at your own risk
* New `multibanco_ifthen_cancel_unpaid_orders` filter to be able to enable order auto cancelation by WooCommerce, if “Manage stock” and “Hold stock (minutes)” are configured, by returning `true` - use at your own risk
* Bumped `Tested up to` and `WC tested up to` tag

= 2.0 =
* [Twilio SMS Notifications](https://woocommerce.com/products/twilio-sms-notifications/) plugin integration: it’s now possible to add Multibanco payment details to the SMS message sent by this plugin by using the %multibanco_ifthen% variable on the message template
* Small improvements in coding standards
* readme.txt improvements

= 1.9.4 =
* Support for new special entities that allow using the order id to generate the reference (because no check digits are needed), and, in the future, will also allow expiration date
* Better feedback on the logs location on WooCoommerce 3.0 and above

= 1.9.3.5 =
* Rollback the French translation to the local plugin folder, because it’s still not approved on GlotPress

= 1.9.3.4 =
* Tested with WooCommerce 3.2
* Added `WC tested up to` tag on the plugin main file
* Bumped `Tested up to` tag

= 1.9.3.3 =
* Avoid duplicate email instructions in some edge cases (fix)

= 1.9.3.2 =
* Avoid duplicate email instructions in some edge cases

= 1.9.3.1 =
* Removed the translation files from the plugin `lang` folder (translations are now managed width [WordPress.org’s GlotPress tool](https://translate.wordpress.org/projects/wp-plugins/multibanco-ifthen-software-gateway-for-woocommerce) and will be automatically downloaded from there)

= 1.9.3 =
* Fixed text domain (changed from `multibanco_ifthen_for_woocommerce` to `multibanco-ifthen-software-gateway-for-woocommerce`) to make it compatible with WordPress.org translation system (Glotpress)
* Fix several strings that were using the `woocommerce` textdomain instead of our own
* Bumped `Tested up to` tag

= 1.9.2 =
* Using `WC()` instead of `$woocommerce`
* Using `wc_reduce_stock_levels()` instead of `$order->reduce_order_stock()` on WooCommerce 3.0 and above
* Using `WC()->customer->get_billing_country()` instead of `WC()->customer->get_country()` on WooCommerce 3.0 and above

= 1.9.1 =
* Started using the new WooCommerce 3.0 [logging system](https://woocommerce.wordpress.com/2017/01/26/improved-logging-in-woocommerce-2-7/)
* Quick (and dirty) fix for [a bug on WooCommerce 3.0](https://github.com/woocommerce/woocommerce/issues/13966) that is not allowing payment gateways to add information to transactional emails
* Improved debug logging

= 1.9 =
* Tested with WooCommerce 3.0.0-rc.2
* Changed version tests from 2.7 to 3.0
* New WC_Multibanco_IfThen_Webdados class for better code organization
* New WC_Order_MB_Ifthen class (extends WC_Order) to be used by the plugin to get and set order details
* Bumped `Tested up to` tag

= 1.8.9 =
* Multibanco payment option is now not shown if the currency is not Euro (Thanks @topsolutions)
* Bumped `Tested up to` tag

= 1.8.8.2 =
* Bumped `Tested up to` tag

= 1.8.8.1 =
* Small CSS fix so that the payment instructions table on the “Thank You” page is not partially hidden on some mobile devices (Thanks Jorge Fonseca for the report)

= 1.8.8 =
* WPML Fix: Shows the payment instructions on the correct language on the “Thank You” page and on Order Status and Customer Notes emails
* Spanish basic translation (email strings only)

= 1.8.7 =
* Started preparations for the new order meta setter and getter functions [coming on WooCommerce 2.7](https://github.com/woocommerce/woocommerce/issues/10071#issuecomment-254797719)
* New `mbifthen_format_ref` function to format the reference with spaces after each 3 number (used by the plugin but can also be used externally)
* New `multibanco_ifthen_format_ref` applied on the string to be returned from the `mbifthen_format_ref` function
* Updated filters examples

= 1.8.6 =
* Warn the store owner that if he ever changes URL he may have to ask ifthenpay to update the callback URL
* Admin notice in case this plugin is active and WooCommerce is not
* Bumped “Requires at least” tag

= 1.8.5 =
* Small change to avoid Polylang removing the payment instructions from the client emails (Thanks Tiago Restivo for the report)
* Bumped “Tested up to” tag

= 1.8.4 =
* New `multibanco_ifthen_base_ent_subent` filter to be able to change the base Entity and Subentity used to generate the payment details, based on the order, which may be useful for marketplaces
* Settings link on the plugins list
* Bumped “Tested up to” tag

= 1.8.3 =
* French translation (Thanks vinha.pt / vinha.co.uk / vinha.fr)

= 1.8.2 =
* Fix: Fatal error on WooCommerce Subscriptions admin screen if the “Only for Portuguese customers?” option was activated  (Thanks TwistedStudio)
* FAQ update

= 1.8.1 =
* Fix: The callback url sent to ifthenpay would use http:// even if ssl was active
* Bumped “Tested up to” tag

= 1.8 =
* If the order changes value on the backend, normally by adding or removing products, a new reference is created to replace the old one. The customer can be notified of the new reference if that option is checked on the plugin settings
* On orders created on the backend the reference is now created correctly, even if it’s not sent to the customer email because of a WooCommerce bug (that is going to be fixed when this commit goes into production https://github.com/woothemes/woocommerce/commit/7dadae7bc80a842e10e78a972334937ed5c4416a)
* Choose either to include the payment instructions on emails sent to admin, or not
* Better feedback on the payment details info box on the backend
* Small adjustments on the settings screen, typos fixing and code improvments

= 1.7.9.1 =
* Possibility to dismiss the new “Callback” activation notice
* New warning only on the settings page, before the “Callback” activation button


= 1.7.9 =
* Warning for new users that haven’t yet asked ifthenpay for the “Callback” activation
* New `multibanco_ifthen_email_instructions_payment_received` filter to customize the “Multibanco payment received” text on emails
* Bugfix: Sometimes the “Multibanco payment received” wouldn’t show up on the client email
* Small settings screen fixes
* Minor spelling errors correction (Thanks @dmatos)

= 1.7.8 =
* Better reporting if it’s not possible to generate the reference

= 1.7.7.1 =
* Fixed “Tested up to” field

= 1.7.7 =
* WordPress 4.4, WooCommerce 2.4.12 and PHP 7 compatibility check - All good!

= 1.7.6 =
* Changes to the settings page in order to validate Entity and Subentity input
* Bumped required WordPress version to match the same requirements WooCommerce has (4.1)

= 1.7.5.1 =
* `readme.txt` changes

= 1.7.5 =
* It’s now possible to set the extra instructions text below the payment details table on the “Thank you” page and “New order” email on the plugin settings screen
* Small adjustments on the WPML detection code
* Fix: Polylang conflict (Thanks fana605)
* Updated filters examples

= 1.7.4.1 =
* Minor fixes on wrong links to set the WooCommerce currency (Thanks JLuis Freitas)

= 1.7.4 =
* Added new debug variables to the callback URL: date and time of payment and used terminal (this information will only be visible on the “Order Notes” administration panel)
* Minor spelling errors correction

= 1.7.3.1 =
* Changelog version fix

= 1.7.3 =
* Bug fixes on `filters_examples.php` on the `multibanco_ifthen_email_instructions_table_html` and `multibanco_ifthen_sms_instructions` examples (props to Jorge Fonseca)

= 1.7.2 =
* Small changes on the callback validation to better debug possible argument errors

= 1.7.1 =
* Ask ifthenpay for “Callback” activation directly from the plugin settings screen
* Settings screen fields re-organization in a more logical order
* Adjustments in the plugin description and FAQ
* Minor fixes to avoid a PHP Notice on WPML string registration

= 1.7.0.2 =
* Fixing version numbers

= 1.7.0.1 =
* Uploading missing images

= 1.7 =
* Official ifthenpay plugin status \o/
* New “SMS payment instructions” class to be able to integrate with SMS sending plugins in the future
* New `multibanco_ifthen_sms_instructions` filter to customize the SMS payment instructions
* [WooCommerce - APG SMS Notifications](https://wordpress.org/support/plugin/woocommerce-apg-sms-notifications) plugin integration: it’s now possible to add the Multibanco payment details to the SMS message sent by this plugin by using the %multibanco_ifthen% variable on the message template
* Shows alternate callback URL on WordPress installations that don’t have pretty permalinks active (Why? Oh why??)
* New callback test tool on the edit order screen, if WP_DEBUG is set to true
* WPML: Tries to fix the locale if WPML is active and we’re loading via AJAX
* WPML: Get’s the title in the correct language for the icon’s alt attribute
* WPML: Shows the payment instructions on the correct language on the “Thank You” page and on Order Status and Customer Notes emails
* Now using WooCommerce’s `payment_complete` function so that orders with only downloadable items go directly to completed instead of processing
* Fix: eliminates duplicate “payment received” messages on emails
* Fix: Use “new” (2.2+) WooCommerce order status when searching for orders to be set as paid via callback (shame on us)
* “Commercial information” and “Technical support” information and links on the right of the plugin settings screen
* Adjustments in the plugin description and FAQ

= 1.6.2.1 =
* Fixes a fatal error if WPML String Translation plugin is not active

= 1.6.2 =
* WPML compatibility: You can now set the English title and description at the plugin’s settings screen and then go to WPML > String Translation to set the same for each language
* Fix: `get_icon()` throw a notice

= 1.6.1 =
* It’s now possible to change the payment gateway icon HTML using the `woocommerce_gateway_icon` filter. See `filters_examples.php`
* Fix: Debug log path.
* Fix: `multibanco_ifthen_thankyou_instructions_table_html` filter example had an error
* Minor Portuguese translation tweaks.

= 1.6 =
* It’s now possible to decide either to reduce stock when the payment is confirmed via callback (default) or when the order is placed by the client. On the first case you don’t have to fix the stock if the order is never paid but you’ll also not have the quantity reserved for this order. On the second case you’ll have to manually fix the stock if the order is never paid.
* There’s 2 filters that allow changing the payment instructions on both the “Thank you” page and on the client email. You can choose either to manipulate the default HTML or create your own. See `filters_examples.php`
* Minor Portuguese translation tweaks.

= 1.5.1 =
* Minor visual tweaks
* Fix: eliminated some notices and warnings

= 1.5 =
* It’s now possible to enable this payment method only for orders below a specific amount
* Fix: No more values passed by reference, in order to avoid “deprecated” notices from PHP
* Fix: Bug on the option introduced on version 1.3

= 1.4.2 =
* Removed unused `add_meta_box` code

= 1.4.1 =
* Minor Multibanco logo improvements (Thanks Gumelo)
* Fix: Small bug when detecting multisite installs

= 1.4 =
* WordPress Multisite support

= 1.3 =
* It’s now possible to enable this payment method only for orders above a specific amount

= 1.2 =
* Added the ability to receive callback logs on an email address
* Fixed “Order Status Emails for WooCommerce” plugin detection (soon to be released)
* Fixed “ifthenpay” link

= 1.1 =
* Changed plugin name and instructions to reflect the new company/gateway name “ifthenpay” instead of “Ifthen Software”
* Fix: Changed textdomain calls from a variable to a string
* Fix: Icon and banner URL now uses `plugins_url` function instead of `WP_PLUGIN_URL` constant
* “Order Status Emails for WooCommerce” plugin integration (soon to be released, or not...)

= 1.0.1 =
* Fix: On some environments some labels were not being translated correctly
* Minor changes to allow running upgrade tasks

= 1.0 =
* Initial release.