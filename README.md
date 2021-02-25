# README

# Contents

- Introduction
- Prerequisites
- Installing the payment module
- License

# Introduction

This PrestaShop module provides an easy method to integrate with the payment gateway.
 - Supports versions: **1.7 - 1.7.56**

# Prerequisites

- The module requires the following prerequisites to be met in order to function correctly:
    - SSL **NB: HTTPS is expected to be in place as the payment gateway will respond over SSL when redirecting the user's browser. Failure to provide an environment where HTTPS traffic is possible, will result in the 3DSv2 payment flow failing***

> Please note that we can only offer support for the module itself. While every effort has been made to ensure the payment module is complete and bug free, we cannot guarantee normal functionality if unsupported changes are made.

# Installing and configuring the module

1. Copy the contents of the httpdocs folder into your root PrestaShop directory. If you are asked if you want to replace any existing files, click “Yes”.
2. Log in to the Admin area of PrestaShop, then from the left menu, click 'Modules' and then 'Modules Catalog'. Next, from the search box start typing "Cardstream" and when the module shows up below (follow the second paragraph if this does not occur); Click 'Install'. The page should automatically refresh when the module installs. Clicking on 'Configure' will automatically direct you to the module settings.
3. From here, enter your Merchant ID, Currency Code, Country ID and Passphrase. In the 'Frontend' box, enter a sentence asking your customer to pay with Cardstream, i.e. "Process payments with Cardstream", or "Cardstream".
4. Whilst in the module settings, go to 'Manage Hooks' at the top right of the page. Then go to 'Transplant a module' at the top right, select 'Cardstream Payment Gateway' as the module and transplant it to 'displayOrderConfirmation'. Repeat the latter to additionally add the 'displayPaymentReturn' hook.
5. Click 'Save Changes'.


License
----
MIT
