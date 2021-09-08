Compatibility
=================================

Compatible with Version 1.7 and above only

Payment Network module for PrestaShop
=================================


## Installation:


**Step 1:**

Upload archive to platform by logging in to the Admin area of PrestaShop, 
then from the left menu, click `Modules` and then `Modules Catalog`
click button `Upload a module` and upload the zip file.

**Step 2:**

Next, from the search box start typing "Payment Network" and when the module shows up below;
Click `Install`. The page should automatically refresh when the module installs.
Clicking on `Configure` will automatically direct you to the module settings.

**Step 3:**

From here, enter your `Merchant ID` and `Passphrase`. 
In the `Frontend` box, enter a sentence asking your customer to pay with Payment Network,
i.e. "Process payments with Payment Network", or "Payment Network".



## Installation Manual:

**Step 1:**

Copy the contents of the `httpdocs` folder into your root PrestaShop directory. If you are asked if you want to replace any existing files, click “Yes”.

**Step 2:**

Next, from the search box start typing "Payment Network" and when the module shows up below;
Click `Install`. The page should automatically refresh when the module installs.
Clicking on `Configure` will automatically direct you to the module settings.

**Step 3:**

From here, enter your `Merchant ID` and `Passphrase`.
In the `Frontend` box, enter a sentence asking your customer to pay with Payment Network,
i.e. "Process payments with Payment Network", or "Payment Network".

Branded Version
----------------------------

Module is designed to be configured according to customer needs, and it could be easily branded via Settings API,
for cases when it is needed a different name in plugins section or different defaults in settings
please follow the following steps:

1. Update plugin defaults, located in `httpdocs/modules/paymentnetwork/config.php`
2. Update how platform sees the modules by changing `httpdocs/modules/paymentnetwork/config.xml`

**NOTE:**
- in `httpdocs/modules/paymentnetwork/config.xml` it is safe to change following fields [`description`, `displayName`, `author`]
- Processing direct payments without HTTPS enabled on PrestaShop is prohibited.
- All settings must be saved before use.
- The Frontend box MUST be filled in for the module to work. Click Update Settings. 
