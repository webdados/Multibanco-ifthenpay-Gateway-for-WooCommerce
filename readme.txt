=== Multibanco, MBWAY and Payshop (IfthenPay) for WooCommerce ===
Contributors: webdados, ifthenpay
Tags: woocommerce, multibanco, mbway, payshop, payment, pagamentos, gateway, mb way, mobile, atm, debit card, credit card, bank, ecommerce, e-commerce, ifthen, ifthen software, ifthenpay, webdados, sms, php7, cartão de débito, cartão de crédito, cartões, gdpr
Author URI: https://www.webdados.pt
Plugin URI: https://www.webdados.pt/wordpress/plugins/multibanco-ifthen-software-gateway-woocommerce-wordpress/
Requires at least: 4.1
Tested up to: 5.5
Requires PHP: 5.6
Stable tag: 4.4.4

This plugin allows customers with a Portuguese bank account to pay WooCommerce orders using Multibanco (Pag. Serviços), MB WAY and Payshop through IfthenPay’s payment gateway.

== Description ==

“Pagamento de Serviços” (payment of services) on Multibanco (Portuguese ATM network), or Home Banking services, is the most popular way to pay for services and (online) purchases in Portugal. Portuguese consumers trust the “Multibanco” system more than any other.
This plugin will allow you to generate a payment Reference the customer can then use to pay for his WooCommerce order, through an ATM or Home Banking service.

The new MB WAY (using the customer mobile phone number) and CTT Payshop payment methods are also available.

This is the official [IfthenPay](https://ifthenpay.com) plugin, and a contract with this company is required. Technical support is provided by [Webdados](https://www.webdados.pt).

**This plugin will soon require WordPress 4.4, WooCommerce 3.0 and PHP 7.0 or above.**
If you cannot update any of these components, you should check the changelog of this plugin to find out which is the last version you can use in your scenario.

**If you’re using WooCommerce 4.2.0 and experiencing problems with orders changing values and new references being created [check this out](https://wordpress.org/support/topic/importante-encomenda-muda-de-valor-no-woocommerce-4-2-0/) and report issues [here](https://github.com/woocommerce/woocommerce/issues/26582)**

= Are you already issuing automatic invoices on your WooCommerce store? =

If not, get to know our new plugin: [Invoicing with InvoiceXpress for WooCommerce](https://wordpress.org/plugins/woo-billing-with-invoicexpress/)

= Features: =

* Generates a Multibanco Reference for simple payment on the Portuguese ATM network or Home Banking service;
* Allows the customer to pay using MB WAY using his mobile phone;
* Generates a Payshop Reference for simple payment on the [Payshop agents network](https://www.payshop.pt/fepsapl/app/open/showSearchAgent.jspx), CTT stores or post offices available all over Portugal;
* Automatically changes the order status to “Processing” (or “Completed” if the order only contains virtual downloadable products) and notifies both the customer and the store owner, if the automatic “Callback” upon payment is activated; 
* Automatic “Callback” can be activated upon request to IfthenPay, via the plugin settings screen for each payment method;
* Shop owner can set minimum and maximum order totals for each payment gateway to be available;
* Ability to reduce stock when the order is created or paid;
* Allows searching orders (in the admin area) by Multibanco or Payshop Reference;
* Integration for 3rd party SMS notification plugins (only Multibanco and Payshop):
	* [WooCommerce - APG SMS Notifications](https://wordpress.org/plugins/woocommerce-apg-sms-notifications/)
	* [Twilio SMS Notifications](https://woocommerce.com/products/twilio-sms-notifications/);
	* [YITH WooCommerce SMS Notification](https://yithemes.com/themes/plugins/yith-woocommerce-sms-notifications/);
	* [E-goi SMS Orders Alert/Notifications for WooCommerce](https://pt.wordpress.org/plugins/sms-orders-alertnotifications-for-woocommerce/) (integration implemented on their plugin)
	* Other providers can be added upon request (under quotation);
* WPML tested and compatible (for multilingual shops);
* Polylang tested;
* [WooCommerce Subscriptions](https://woocommerce.com/products/woocommerce-subscriptions/) (experimental) integration (only Multibanco and MB WAY);
* [WooCommerce Deposits by webtomizer](https://woocommerce-deposits.com/) (experimental) integration;
* [WooCommerce Blocks](https://wordpress.org/plugins/woo-gutenberg-products-block/) checkout block (experimental) integration (only Multibanco);
* PHP7 tested and compatible;

= Premium add-ons: =

* [Entity per Category](https://shop.webdados.com/product/multibanco-and-mbway-ifthenpay-entity-per-category/):
	* Set a base Multibanco Entity and Subentity, or MB WAY Key, at the product category level, so you can get the payment on different accounts based on the order;
	* Lock the cart so you can only have products from the same account on each order;

== Installation ==

* Use the included automatic install feature on your WordPress admin panel and search for “Multibanco, MBWAY and Payshop (IfthenPay) for WooCommerce”.
* Multibanco: Go to WooCoomerce > Settings > Checkout > Pagamento de Serviços no Multibanco and fill in the data provided by IfthenPay (Entity and Subentity) in order to use this payment method. A contract with IfthenPay is mandatory to receive this data.
* MB WAY: Go to WooCoomerce > Settings > Checkout > Pagamento MB WAY no telemóvel and fill in the data provided by IfthenPay (MB WAY Key) in order to use this payment method. A contract with IfthenPay is mandatory to receive this data.
* Payshop: Go to WooCoomerce > Settings > Checkout > Pagamento na rede de agentes Payshop and fill in the data provided by IfthenPay (Payshop Key) in order to use this payment method. A contract with IfthenPay is mandatory to receive this data.
* Make sure you ask IfthenPay to activate the “Callback” on their side with the URL and Anti-phishing key provided in the settings screen. There is a feature in each payment method screen that allows you to make this request via email.
* Start receiving payments :-)

== Frequently Asked Questions ==

= Can I start receiving payments right away? Show me the money! =

Nop! You have to sign a contract with IfthenPay in order to activate this service. Go to [https://ifthenpay.com](https://ifthenpay.com) for more information.

= I’m a individual and not a registered business. Can I use this plugin? =

No. IfthenPay only provides this service to registered businesses and equivalent (like tax registered freelancers, for example). You should [contact them](https://ifthenpay.com/#contactos) if you need additional details on this matter.

= The generated Multibanco reference does not contain the order number. How do I know which order was paid? =

IfthenPay will send you an email each time a reference is paid, but the reference does not contain the order number. By design, our plugin does not include the order number in the reference due to the way WooCommerce/WordPress set orders/posts IDs.

Anyway, you do not need the order number in the reference, because our plugin uses a callback mechanism. IfthenPay automatically notifies WooCommerce when a specific Multibanco reference is used for payment. The order is linked to the reference in the database and, also automatically, will be set as paid.

If you still need to know to which order a reference is linked to, use the search box in the WooCommerce Orders administration screen.

= Can I test the callback call to simulate a payment and foresee what happens when a real payment is made by a customer? =

Yes, you can. Edit your wp-config.php file and set WP_DEBUG to true. Then, place a test order in the frontend using Multibanco or MB WAY. Go to the order edit screen and click the "Simulate callback payment" button.

= Can I use this plugin / IfthenPay service on more than one website? =

Yes, but not with the same Multibanco entity and subentity or MB WAY Key. Ask IfthenPay for different credentials for each website you need the service to be available. There is no extra costs involved, and you can even route payments to distinct bank accounts.

= Can I change the payment instructions look and feel in the “Thank you” page and/or the new order email, as well as the SMS message format? =

Yes you can! But you should know your way around WordPress filters. There are filters to do this and you can find examples within `hooks-examples.php`.

= Can I change the Multibanco or MB WAY icon in the checkout page? =

There are also filters for this. See `hooks-examples.php`.

= I want to charge an additional fee for Multibanco and/or MB WAY payments. How should I do it? =

You shouldn’t! To our knowledge, it’s illegal under [Portuguese law](https://www.bportugal.pt/sites/default/files/anexos/legislacoes/dl3ano2010.PDF) and a [European directive](https://europa.eu/youreurope/business/finance-funding/making-receiving-payments/electronic-cash-payments/index_en.htm) to charge an extra fee based on the payment method chosen by the customer.
If you don’t care about legislation, plugins are available that allow you to set extra fees per payment method. Please, don’t ask us for support on this.

= How much time does the customer have to pay with MB WAY? =

The MB WAY payment requests expire after 5 minutes.

= [WPML] My website is multilingual. Will I be able to use this plugin? =

Yes. This plugin is officially WPML compatible. You will need the WPML and WPML String Translation Plugins (alongside WooCommerce Multilingual, which is mandatory for any WooCommerce + WPML install). 

= [WPML] How can I translate the payment method title and description the customer sees in the checkout page to secondary languages? =

Go to WPML > String Translation > Search and translate the `multibanco_ifthen_for_woocommerce_gateway_title`, `multibanco_ifthen_for_woocommerce_gateway_description`, `mbway_ifthen_for_woocommerce_gateway_title` and `mbway_ifthen_for_woocommerce_gateway_description` strings in the `woocommerce` domain. Don’t forget to check the “Translation is complete” checkbox and click “Save”. You should also translate the “Extra instructions” strings by searching the `multibanco_ifthen_for_woocommerce_extra_instructions` string on the `multibanco_ifthen_for_woocommerce` domain and the `mbway_ifthen_for_woocommerce_extra_instructions` string on the `mbway_ifthen_for_woocommerce` domain.

= [SMS] How to include the Multibanco payment instructions in the SMS sent by “WooCommerce - APG SMS Notifications”? =

Go to WooCommerce > SMS Notifications and add the `%multibanco_ifthen%` variable to “Order on-hold custom message”.

= [SMS] How to include the Multibanco payment instructions in the SMS sent by “Twilio SMS Notifications”? =

Go to WooCommerce > Settings > SMS and add the `%multibanco_ifthen%` variable to “Customer Notifications“ > “On Hold SMS Message”.

= [SMS] How to include the Multibanco payment instructions in the SMS sent by “YITH WooCommerce SMS Notification”? =

Go to YITH Plugins > SMS Notifications > SMS Settings and add the `{multibanco_ifthen}` variable to “On hold”.

= [Advanced] Can I use a specific Entity or Subentity based on order details? =

Yes, you should use the `multibanco_ifthen_base_ent_subent` filter. See `hooks-examples.php`.

= [Advanced] Can I use a specific MB WAY Key based on order details? =

Yes, you should use the `multibanco_ifthen_base_mbwaykey` filter. See `hooks-examples.php`.

= [Advanced] The order is set "On Hold", can I make it "Pending" by default? =

I don’t know why on earth you would want to do this, but… yes, you can. Just return `false` to the `multibanco_ifthen_set_on_hold` and/or `mbway_ifthen_set_on_hold` filter.
Be advised that no "new order" email, with payment instructions, will be sent to the customer unless you use some plugin or custom code to force it.

= [Advanced] I’ve set up WooCommerce to cancel unpaid orders after x minutes, why are my Multibanco and/or MB WAY orders not being canceled? =

WooCommerce only automatically cancels "Pending" orders, not "On Hold", because these orders are set to be paid by offline methods (like this one), and payment can occur at any time, even after the order is canceled. Still, if you want to take that risk, just return `true` to the `multibanco_ifthen_cancel_unpaid_orders` and/or `mbway_ifthen_cancel_unpaid_orders` filter.
You can also restore that order’s product stock by returning `true` to the `multibanco_ifthen_cancel_unpaid_orders_restore_stock` and/or `mbway_ifthen_cancel_unpaid_orders_restore_stock` filter, although the WooCommerce team [doesn’t recommend](https://github.com/woocommerce/woocommerce/issues/3712#issuecomment-23650563) it.
Be advised the Multibanco reference will still be active and can be paid at any ATM or home banking service.

= [Advanced] Can I prevent the plugin from adding the payment instructions and/or the payment received message to emails? =

You can use the `multibanco_ifthen_email_instructions_pending_send` and/or `mbway_ifthen_email_instructions_pending_send` filter: return false and the payment instructions won’t be included in the "new order" email – we do not recommend you to do it, though.
You can use the `multibanco_ifthen_email_instructions_payment_received_send` and/or `mbway_ifthen_email_instructions_payment_received_send` filter: return false and the payment received message won’t be included in the "processing" email.

= IfthenPay says my callback URL is returning a 404 error. Should I sit in a corner and cry or is there a solution? =

Don’t cry! There’s a solution!
You probably have weird permalink settings (or permalinks not set at all) in your WordPress installation.
Tell them to change the callback URL from `https://yourwebsite/wc-api/WC_Multibanco_IfThen_Webdados/?chave=[CHAVE_ANTI_PHISHING]&entidade=[ENTIDADE]&referencia=[REFERENCIA]&valor=[VALOR]` to `https://yourwebsite/?wc-api=WC_Multibanco_IfThen_Webdados&chave=[CHAVE_ANTI_PHISHING]&entidade=[ENTIDADE]&referencia=[REFERENCIA]&valor=[VALOR]`.

= I need technical support. Who should I contact, IfthenPay or Webdados? =

Although this is the official IfthenPay WooCommerce plugin, development and support is provided by [Webdados](https://www.webdados.pt).
For free/standard support you should use the support forums here at WordPress.org
For premium, urgent and experimental integrations support or custom developments you should contact [Webdados](https://www.webdados.pt/contactos/). Charges may (and most certainly will) apply.

= Is this plugin compliant with the European Union General Data Protection Regulation (GDPR)? =

This plugin does not collect or send to Webdados (the plugin author) or IfthenPay (the payment processor) any private data of the website where it’s installed, it’s customers or the orders themselves.
In the MB WAY module, the mobile phone number is collected to request the payment authorization and it can be legitimately processed based on article 6 (1) (b) of the GDPR.
IfthenPay’s privacy policy can be found at [https://ifthenpay.com/termos-politica-privacidade/](https://ifthenpay.com/termos-politica-privacidade/)

= Can I contribute with a translation? =

Sure. Go to [GlotPress](https://translate.wordpress.org/projects/wp-plugins/multibanco-ifthen-software-gateway-for-woocommerce) and help us out.

== Changelog ==

= 4.4.4 - 2020-08-05 =
* You can safely update this plugin if you’re running WooCommece 2.6.0 or newer **but we’ll very soon drop support for WooCommerce previous to 3.0 (which was launched in April 2017)**
* New WooCommerce Blocks checkout only if the feature plugin is installed and activated
* Bugfix on the Payshop callback activation request
* Fix a PHP notice
* Tested with WordPress 5.5-RC1-48708, WooCommerce 4.4.0-rc.1 and WooCommerce Blocks 3.1.0


= 4.4.3 - 2020-07-23 =
* Revert showing the Multibanco "payment received" message on order completed emails (introduced on 4.4.0)
* Only show WooCommerce Subscriptions options if the plugin is active
* Only enable Multibanco support for the WooCommerce Blocks checkout if WooCommerce Blocks version is 3.0.0. or above and the support is enabled via the payment method options

= 4.4.2 =
* Bugfix when WooCommerce Blocks 3.0.0 or above is active
* Try to fix a fatal error when themes override the WooCommerce email templates with old (pre WooCommerce 2.6.0) versions
* Tested with WordPress 5.5-beta3-48556, WooCommerce 4.3.1 and WooCommerce Blocks 3.0.0

= 4.4.1 =
* Bugfix checking if order is paid when the "WooCommerce Order Status Manager" (by SkyVerge) plugin is active

= 4.4.0 =
* New `ifthen_unpaid_statuses` filter to allow developers to set additional valid "unpaid" statuses for Multibanco, MBWAY and Payshop orders, besides the default ones ("on-hold", "pending" and "partially-paid"). The statuses are used for callback validation, SMS message template, show order as unpaid on the backoffice, show "Pay" button on My Account orders list, issue new references if order value changes on the backoffice, reduce order stock rules, thank you page and email payment instructions.
* Enforce requirement of WooCommerce 2.6.0 or above and bumped the `WC requires at least` tag accordingly
* Enforce requirement of WordPress 4.4 or above and bumped the `Requires at least` tag accordingly
* New filter `ifthen_debug_log_extra` that will allow developers to further debug the Multibanco reference generation (for now)
* Try to fix a (very odd) behavior where the customer is redirected to the "pay order" page when completing checkout, which will generate a duplicate Multibanco payment reference (as expected).
* New filters to hide the "Pay" button on "My Account" (which we do not recommend): `multibanco_ifthen_hide_my_account_pay_button`, `mbway_ifthen_hide_my_account_pay_button`, `payshop_ifthen_hide_my_account_pay_button`
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
* Better handling when the customer decides to change payment method in "My Account"
* Only apply our WooCommerce 4.2.0 fix if "Prices entered with tax" is set to "Yes"
* Code refactoring to prepare the next phase of supporting only WooCommerce 3.0 and above

= 4.2.2 =
* **Temporarily, while [this WooCommerce bug](https://github.com/woocommerce/woocommerce/issues/26582) is not solved, the value will not be matched when checking the callback and no new Multibanco or Payshop reference will be issued if the order changes value**
* Clarification on the settings page that the same set of entities or keys should never be used in more than one platform
* Links to the Payshop agents and CTT stores search on the Payshop method extra instructions default message
* Fix MB WAY phone number field hidden on some themes
* Fix MB WAY and Payshop key fields appearance on the payment method settings
* Show the Pay button on My Account for Multibanco and Payshop "On hold" orders
* Better information when MB WAY order is already paid for
* Better debug when requesting the MB WAY payment to the IfthenPay webservice
* readme.txt tweaks
* Tested with WordPress 5.5-alpha-47923 and WooCommerce 4.2.0

= 4.2.1 =
* Bugfix issuing new Multibanco or Payshop payment details when the order value is changed on wp-admin on WooCommerce 4.0 and above
* Extensions and other premium plugins list on the payment gateways settings page
* Tested with WordPress 5.5-alpha-47547 and WooCommerce 4.0.1

= 4.2.0 =
* Experimental: Automatically cancel unpaid orders after the Multibanco reference expires, if the "Incremental references with expiration date" mode is active
* Bugfix when hiding Multibanco settings fields, if the "Incremental references with expiration date" mode is active
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
* Fix bug on the Payshop intructions on the thank you page when the reference has no expiration date
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
* Bugfix on the WooCommerce Subscriptions integration (Thanks (@vascothemudo)
* Tested with WooCommerce 3.6.2

= 3.6.3 =
* Add the `partially-paid` status to Multibanco valid pending payment status when checking the callback
* New `multibanco_ifthen_valid_callback_pending_status` and `mbway_ifthen_valid_callback_pending_status` filters when checking for pending payment orders on Multibanco and MB WAY callbacks (WooCommerce >= 3.0)
* Tested with WordPress 5.1.1 and WooCommerce 3.6.0-rc.1

= 3.6.2.2 =
* 30 seconds timeout instead of 10 seconds when calling IfthenPay’s MB WAY webservice, because SIBS is having performance problems which results in IfthenPay being unable to reply to our request on time

= 3.6.2.1 =
* Check for WooCommerce below 2.2 (apparently it’s still around) and stop the plugin initialization if found

= 3.6.2 =
* 10 seconds timeout instead of 5 seconds when calling IfthenPay’s MB WAY webservice
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
* Support for Multibanco references with expiration date (needs activation by IfthenPay)
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
* New option (activated by default) to force the resending of the "New Order" email (not the British Synthpop band), when the Multibanco payment is done via callback (this was happening erroneously before we fixed the stock management issue on 3.4.2, but we understand this is usefull for the Multibanco payment method)

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
* Bugfix: Reduce stock correctly according to settings since [WooCommerce changed it’s behaviour in 3.4.0](https://github.com/woocommerce/woocommerce/commit/70c9cff608761fcd48b57f709059e00b1ffeee38#diff-27a48ce67fa604181c90b4bb464164ac)

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
* `index.php` file because "best practices"
* Small coding standards fixes

= 3.2 =
* New behaviour for special entities that don’t allow repeated payments in a specific time frame (only for WooCommerce 3.0 and above)
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
* Fixed a fatal error bug if the order is not found when the MB WAY callback is invoked by IfthenPay
* Better feedback to the customer, informing that there are only 5 minutes to complete the payment – because it seems SIBS has changed the timeout and told no one about it  ¯\_(ツ)_/¯

= 3.0.5 =
* Better WooCommerce detection
* Always round MB WAY values to two decimals
* Clean problematic characters from the MB WAY payment description
* Small fixes
* Bumped `WC tested up to` tag

= 3.0.4 =
* Debug when contacting the IfthenPay webservice to create the MB WAY payment request (shame on us...)
* Better feedback to the customer, informing that there are only 15 minutes to complete the payment
* Bumped `WC tested up to` tag

= 3.0.3 =
* Better (and persistent) feedback related to the callback still not being asked to IfthenPay
* Removed the `mbway_ifthen_set_on_hold` filter that no longer makes sense since 3.0.2

= 3.0.2 =
* Changed the default MB WAY order status to "pending", because there’s a time limit to pay for the order. Orders will be automatically canceled if you use the "Manage stock" and "Hold stock" settings on WooCommerce. You can use the "on-hold" behaviour like in Multibanco if you return false to `mbway_ifthen_order_initial_status_pending`. (Thanks for the mentoring @chrislema)
* Fix: Multibanco logo was not showing up on the email notifications after 3.0
* Fix: MB WAY Callback testing when WP_DEBUG = true
* Fix: Checking for "pending" order status if applicable
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
* Show payment instructions in the order details screen on "My Account"; 

= 2.0.4.1 =
* Version fix

= 2.0.4 =
* [YITH WooCommerce SMS Notification](https://yithemes.com/themes/plugins/yith-woocommerce-sms-notifications/) plugin integration: it’s now possible to add Multibanco payment details to the SMS message sent by this plugin by using the {multibanco_ifthen} variable on the message template

= 2.0.3 =
* New `multibanco_ifthen_email_instructions_pending_send` filter to which you can return false so that the payment instructions are not included in the "new order" email, although we do not recommend doing it
* New `multibanco_ifthen_email_instructions_payment_received_send` filter to which you can return false so that the payment received message is not included in the "processing" email
* Bumped `Tested up to` tag 

= 2.0.2 =
* Database abstraction on WooCommerce 3.0 and above, by using `wc_get_orders`
* Small adjustments

= 2.0.1 =
* New `multibanco_ifthen_set_on_hold` filter to be able to leave the order pending instead of on hold by returning `false` - use at your own risk
* New `multibanco_ifthen_cancel_unpaid_orders` filter to be able to enable order auto cancelation by WooCommerce, if "Manage stock" and "Hold stock (minutes)" are configured, by returning `true` - use at your own risk
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
* Small CSS fix so that the payment instructions table on the "Thank You" page is not partially hidden on some mobile devices (Thanks Jorge Fonseca for the report)

= 1.8.8 =
* WPML Fix: Shows the payment instructions on the correct language on the “Thank You” page and on Order Status and Customer Notes emails
* Spanish basic translation (email strings only)

= 1.8.7 =
* Started preparations for the new order meta setter and getter functions [coming on WooCommerce 2.7](https://github.com/woocommerce/woocommerce/issues/10071#issuecomment-254797719)
* New `mbifthen_format_ref` function to format the reference with spaces after each 3 number (used by the plugin but can also be used externally)
* New `multibanco_ifthen_format_ref` applied on the string to be returned from the `mbifthen_format_ref` function
* Updated filters examples

= 1.8.6 =
* Warn the store owner that if he ever changes URL he may have to ask IfthenPay to update the callback URL
* Admin notice in case this plugin is active and WooCommerce is not
* Bumped "Requires at least" tag

= 1.8.5 =
* Small change to avoid Polylang removing the payment instructions from the client emails (Thanks Tiago Restivo for the report)
* Bumped "Tested up to" tag

= 1.8.4 =
* New `multibanco_ifthen_base_ent_subent` filter to be able to change the base Entity and Subentity used to generate the payment details, based on the order, which may be useful for marketplaces
* Settings link on the plugins list
* Bumped "Tested up to" tag

= 1.8.3 =
* French translation (Thanks vinha.pt / vinha.co.uk / vinha.fr)

= 1.8.2 =
* Fix: Fatal error on WooCommerce Subscriptions admin screen if the "Only for Portuguese customers?" option was activated  (Thanks TwistedStudio)
* FAQ update

= 1.8.1 =
* Fix: The callback url sent to IfthenPay would use http:// even if ssl was active
* Bumped "Tested up to" tag

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
* Warning for new users that haven’t yet asked IfthenPay for the “Callback” activation
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
* Ask IfthenPay for “Callback” activation directly from the plugin settings screen
* Settings screen fields re-organization in a more logical order
* Adjustments in the plugin description and FAQ
* Minor fixes to avoid a PHP Notice on WPML string registration

= 1.7.0.2 =
* Fixing version numbers

= 1.7.0.1 =
* Uploading missing images

= 1.7 =
* Official IfthenPay plugin status \o/
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
* Fixed “IfthenPay” link

= 1.1 =
* Changed plugin name and instructions to reflect the new company/gateway name “IfthenPay” instead of “Ifthen Software”
* Fix: Changed textdomain calls from a variable to a string
* Fix: Icon and banner URL now uses `plugins_url` function instead of `WP_PLUGIN_URL` constant
* “Order Status Emails for WooCommerce” plugin integration (soon to be released, or not...)

= 1.0.1 =
* Fix: On some environments some labels were not being translated correctly
* Minor changes to allow running upgrade tasks

= 1.0 =
* Initial release.