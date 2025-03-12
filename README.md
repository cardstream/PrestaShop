Compatibility
=================================

Compatible with Version 1.7 and above only

Cardstream module for PrestaShop
=================================


## Installation:


**Step 1:**

Upload archive to platform by logging in to the Admin area of PrestaShop, 
then from the left menu, click `Modules` and then `Modules Catalog`
click button `Upload a module` and upload the zip file.

**Step 2:**

Next, from the search box start typing "Cardstream" and when the module shows up below;
Click `Install`. The page should automatically refresh when the module installs.
Clicking on `Configure` will automatically direct you to the module settings.

**Step 3:**

From here, enter your `Merchant ID` and `Passphrase`. 
In the `Frontend` box, enter a sentence asking your customer to pay with Cardstream,
i.e. "Process payments with Cardstream", or "Cardstream".



## Installation Manual:

**Step 1:**

Copy the contents of the `httpdocs` folder into your root PrestaShop directory. If you are asked if you want to replace any existing files, click “Yes”.

**Step 2:**

Next, from the search box start typing "Cardstream" and when the module shows up below;
Click `Install`. The page should automatically refresh when the module installs.
Clicking on `Configure` will automatically direct you to the module settings.

**Step 3:**

From here, enter your `Merchant ID` and `Passphrase`.
In the `Frontend` box, enter a sentence asking your customer to pay with Cardstream,
i.e. "Process payments with Cardstream", or "Cardstream".

Branded Version
----------------------------

Module is designed to be configured according to customer needs, and it could be easily branded via Settings API,
for cases when it is needed a different name in plugins section or different defaults in settings
please follow the following steps:

1. Update plugin defaults, located in `httpdocs/modules/cardstream/config.php`
2. Update how platform sees the modules by changing `httpdocs/modules/cardstream/config.xml`

**NOTE:**
- in `httpdocs/modules/cardstream/config.xml` it is safe to change following fields [`description`, `displayName`, `author`]
- Processing direct payments without HTTPS enabled on PrestaShop is prohibited.
- All settings must be saved before use.
- The Frontend box MUST be filled in for the module to work. Click Update Settings. 

Disclaimer

Sample code, SDKs and modules have been created as reference material only. Modules are developed and tested against vanilla base platform installs only. Any further module compatibility would need to be tested by the user/merchant/developer. Version support is as shown within the associated VERSION section. All sample code, SDKs and modules offer foundation transaction functionality for merchant and developers to use as a guide and/or to adapt, enhance or otherwise build upon. For the avoidance of doubt, this means that some desired functionality may not be useable or exist. All sample code, SDKs or modules that are used will require complete full end to end testing by the user/merchant/developer. Further to this, use of any sample code, SDKs or modules, Cardstream bears no responsibility for; nor extends any warranty in regard to; nor accepts any liability arising due to any changes or errors in functionality that may result. Developers, merchants or other users of any sample code, SDKs or modules accept these conditions de facto upon usage.
