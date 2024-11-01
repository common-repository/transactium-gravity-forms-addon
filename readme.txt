=== Transactium Gravity Forms AddOn ===
Contributors: transactium
Donate link: http://transactium.com
Tags: transactium, form, forms, gravity, gravity form, gravity forms, gravityforms, payment, payments, credit cards, online payment, ecommerce
Requires at least: 3.9
Tested up to: 5.9
Stable tag: 1.3
License: GPLv2 or later

Build your own custom forms with Gravity Forms and process payments externally via the Transactium Hosted Payment System!

== Description ==

Accept one-time payments from your WordPress site with [Transactium](http://transactium.com) - no coding required.

More to add in future versions!


Current Features

* collect _any_ type of **custom data** from your customer
* accept one-time secure payments
* *display prices and accept payments in __multiple currencies__
* provide custom __payment receipts__
* __create a post__ on your site *only* if a payment was successful
* all payments are *PCI compliant*

No coding required.

> **Transactium Gravity Forms AddOn integrates with _[Gravity Forms](http://www.gravityforms.com)_ — one of the best WordPress visual form builders - to allow you to create custom forms to be processed by Transactium.**
>
> **[Download](https://downloads.wordpress.org/plugin/transactium-gravity-forms-addon.zip)**

== Support ==

> **Problems? Require special customisations? [Contact Us](http://support.transactium.com/support/tickets/new)**

== Current Limitations ==

* Cannot have Transactium activated at the same time as Stripe, Authorize.Net or PayPal Pro Add-Ons
* One payment details form per page

== Installation ==

This section describes how to install and setup the Transactium Gravity Forms AddOn. Be sure to follow *all* of the instructions in order for the Add-On to work properly. If you're unsure on any step, there are [screenshots](https://wordpress.org/plugins/transactium-gravity-forms-addon/screenshots/).

### Requirements

Requires at least WordPress 3.9, PHP 5.5 and _[Gravity Forms](http://www.gravityforms.com)_ 2.1.1.
Current versions are WordPress 5.8, PHP 7.4 and gravity forms 2.5.9

### Steps
 
1. Make sure you have your own copy of _[Gravity Forms](http://www.gravityforms.com)_. This plugin does not include _[Gravity Forms](http://www.gravityforms.com)_. It will work with any of the _[Gravity Forms](http://www.gravityforms.com)_ licenses.

2. You'll also need a [Transactium](http://support.transactium.com/support/tickets/new) account

3. Upload the plugin to your WordPress site. There are three ways to do this:

    * **WordPress dashboard search**

        - In your WordPress dashboard, go to the **Plugins** menu and click the _Add New_ button
        - Search for `Transactium Gravity Forms AddOn`
        - Click to install the plugin

    * **WordPress dashboard upload**

        - Download the plugin zip file by clicking the orange download button on this page
        - In your WordPress dashboard, go to the **Plugins** menu and click the _Add New_ button
        - Click the _Upload_ link
        - Click the _Choose File_ button to upload the zip file you just downloaded

    * **FTP upload**

        - Download the plugin zip file by clicking the orange download button on this page
        - Unzip the file you just downloaded
        - FTP in to your site
        - Upload the `transactium-gravity-forms-addon` folder to the `/wp-content/plugins/` directory

4. Visit the **Plugins** menu in your WordPress dashboard, find `Transactium Gravity Forms AddOn` in your plugin list, and click the _Activate_ link.

5. Visit the **Forms->Settings** menu, select the new _Transactium_ tab, and input your Transactium account information. Save your settings.
for testing you can use 
integ_api
Integ_api1
HPSTag

6. Select the _Settings_ tab and set your desired currency. This will be the currency used for your product transactions.

7. Create a form, adding a Total field and at least one Product field from under _Pricing Fields_.

8. Attach fields to Transactium gateway. Under `Form -> Select field -> Advanced -> Transactium -> Field Name for POST` you may specify any of the below properties:
   * Client_ClientReference, Client_OrderReference, Client_CardHolderName
   * Billing_FullName, Billing_Phone, Billing_Email, Billing_StreetNumber, Billing_StreetName, Billing_AddressUnitNumber, Billing_CityName, Billing_TerritoryCode, Billing_CountryCode, Billing_PostalCode, Billing_Fax, Billing_BirthDateYYYYMMDD
   * Customer_FullName, Customer_Phone, Customer_Email, Customer_StreetNumber, Customer_StreetName, Customer_AddressUnitNumber, Customer_CityName, Customer_TerritoryCode, Customer_CountryCode, Customer_PostalCode, Customer_Fax, Customer_BusinessName, Customer_BusinessRegistrationNumber, Customer_BusinessTaxNumber
   * Shipping_FullName, Shipping_Phone, Shipping_Email, Shipping_StreetNumber, Shipping_StreetName, Shipping_AddressUnitNumber, Shipping_CityName, Shipping_TerritoryCode, Shipping_CountryCode, Shipping_PostalCode, Shipping_Fax, Shipping_BusinessName
   * Appearance_ShopName, Appearance_LanguageCode, Appearance_SiteBGColor
	
	Notes
	* You may specify multiple properties for a single field by separating with commas. e.g. `Billing_FullName,Customer_FullName`
	* It is recommended that you hide (Advanced->Visibility->Hidden) and specify a default value (Advanced->Default Value) accordingly when/if specifying certain fields such as the Appearance_\* fields.

9. In the **Form Settings->Transactium** menu, add a new Transactium payment feed to your form. Attach any billing fields in your form to your payment feed for a complete entry record.

10. Browse to **Form Settings->Confirmations** and add a `Success` and a `Failure` confirmation action for the form.

If you need help, try checking the [screenshots](https://wordpress.org/plugins/transactium-gravity-forms-addon/screenshots/)

== Frequently Asked Questions ==

= Do I need to have my own copy of Gravity Forms for this plugin to work? =
Yes, you need to install the [Gravity Forms plugin](http://www.gravityforms.com/ "visit the Gravity Forms website") for this plugin to work.

= Does this version work with the latest version of Gravity Forms? =
This plugin was developed to target Gravity Forms version 2.1.1 and later. It has not been tested on previous versions of Gravity Forms.

= Your plugin just does not work =
Please contact [support](http://support.transactium.com/support/tickets/new).

== Screenshots ==

1. Activate Gravity Forms and Transactium Gravity Forms AddOn

2. Transactium settings page

3. Currency setting

4. Add one Total field and at least one Product field to your Form (under Pricing Fields)

5. Attach fields to Transactium gateway. Refer to the Installation->Steps[8] section in this document for a list of possible combinations and suggestions

6. Form Settings->Transactium menu

7. Transactium Feed Settings page

8. Form Settings->Confirmations menu. Set up `Success` and `Failure` confirmations as shown.

9. Adding a new `Failure` confirmation. You may use `{entry:transaction_id}` to append the transaction ID to the message.

== Changelog ==

= 1.3 (2022-05-23) =
* added url escaping, and removed trnalsation of dynamic field

= 1.2 (2022-05-19) =
* escaping echos

= unversioned (2021-08-23) =
* Update documentation

= 1.1 (2020-05-15) =
* Trimming of email address


= 1.0 (2017-03-28) =
* Initial release.

== Upgrade Notice ==

= 1.1 =
* Bugfix : Trimming of email address

= 1.0 =

