PayFast PrestaShop Module for PrestaShop v1.7-------------------------------------------------------
Copyright (c) 2008 PayFast (Pty) Ltd
You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.

INTEGRATION:
1. Unzip the module to a temporary location on your computer
2. Copy the “modules” folder in the archive to your base “PrestaShop” folder
- This should NOT overwrite any existing files or folders and merely supplement them with the PayFast files
- This is however, dependent on the FTP program you use
3. Using your FTP program, ensure that the /modules as well as /modules/payfast directories are set to CHMOD 0755, otherwise the validation script will not be accessible to set successful payments as paid.
4. Login to the PrestaShop Back Office console
5. Using the top navigation bar, navigate to Modules
6. Click on Payments & Gateways to expand the options
7. Click on the “Install” button to install the module
8. Once the module is installed, click on “Configure” below the PayFast name.
9. The PayFast options will then be shown, and you will see the module is ready to be tested.
10. Leave everything as per default and click “Save”
11. The module is now ready to be tested with the Sandbox. To test with the sandbox, use the following login credentials when redirected to the PayFast site:
- Username: sbtu01@payfast.co.za
- Password: clientpass

How can I test that it is working correctly?
If you followed the installation instructions above, the module is in “test” mode and you can test it by purchasing from your site as a buyer normally would. You will be redirected to PayFast for payment and can login with the user account detailed above and make payment using the balance in their wallet.

You will not be able to directly “test” a credit card or Instant EFT payment in the sandbox, but you don”t really need to. The inputs to and outputs from PayFast are exactly the same, no matter which payment method is used, so using the wallet of the test user will give you exactly the same results as if you had used another payment method.

I’m ready to go live! What do I do?
In order to make the module “LIVE”, follow the instructions below:

1. Login to the PrestaShop Back Office
2. Using the top navigation bar, navigate to Modules
3. Click on Modules & Services
4. Under PayFast, click on the “Configure” link
5. In the PayFast Settings block, use the following settings:
6. Mode = “Live”
7. Merchant ID = <Login to PayFast -> Integration Page>
8. Merchant Key = <Login to PayFast -> Integration Page>
9. Debugging = Unchecked
10. Click Save

******************************************************************************
*                                                                            *
*    Please see the URL below for all information concerning this module:    *
*                                                                            *
*              https://www.payfast.co.za/shopping-carts/prestashop/          *
*                                                                            *
******************************************************************************
