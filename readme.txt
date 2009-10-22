=== Plugin Name ===
Contributors: belinep
Donate link: http://www.coverthisphotography.com/services/web-designdevelopment/smugwp-wordpress-plugin
Tags: smugmug, digiproofs, pro, photography, redirect, confirm, gallery, lightbox, insert, media, wpg2, smugwp
Requires at least: 2.5
Tested up to: 3.0
Stable tag: 3.04

SmugWP is a WordPress plug-in allowing a user to browse and insert images from their SmugMug galleries into a WordPress Post or Page, as well as provide his/her customers with a simple interface to access their on-line galleries.

== Description ==

Thanks for checking SmugWP out there brotha!

SmugWP started off simple enough; create a gallery for the client, give the client the Gallery ID, the client enters the ID into a simple interface and is redirected to the correct gallery. It is functionality similar to the [DigiProofs](http://www.digiproofs.com/ "DigiProofs") interface.  And things just got a touch out of hand from there.

At this point I would almost call SmugWP the WPG2 of SmugMug.

You can now browse your SmugMug galleries, preview images, and insert them into your Post or Page.  Also, if you have WP Lightbox 2 installed, it can pre-format the insertion to properly display the linked image in a lightbox.

The original purpose of the plugin remains.  It still displays a form to evaluate Gallery IDs (which are, unfortunately global), confirms that you own the ID, and redirects the user to YOUR Gallery.  It now fully supports and confirms both the Gallery ID and the Key (the entire NNNNNNN_XXXXX string).

Simply hide all proofing and delivery galleries, and only display your sample galleries. This keeps your SmugMug interface clean, and ensures that proofing galleries are private and secure. It especially helps if you have a fully customized SmugMug template to match your WordPress site.

Works great for me!  Hope your mileage runs you about the same.

== Installation ==

1. Download and unzip the latest version of the plug-in.
1. If you have a previous version of SmugWP installed, disable it on the WordPress Plugins page.
1. Place the “SmugWP” folder in your “wp-content/plugins/” directory (over write the old folder if it exists).
1. Activate SmugWP on the WordPress Plugins page.
1. Enter your Username and Password on SmugWP Options page, under the Options tab.
1. Designate a page to check for form display (enter the page ID on the SmugWP Options page).
1. Insert “[smugWPform]” in the source of any page or post you want to display the form on.
1. Modify the SmugWP.css file in the SmugWP folder to customize the look of the SmugWP form.

== Frequently Asked Questions ==

= I get an error when I try to access my galleries through SmugWP. =

Check your Username and Password.  Better yet, check your Dashboard, any crazy red messages popping up?  Read them.

= Can I display the SmugWP form inside a PHP template? =

Yes, simply call the function sWP_displayForm(); anywhere where you can execute PHP.  This function does not hook for displaying CSS, as it is too far into the execution of the document, but as long as it is on a WordPress page (template) you should have no problems, as the CSS is hooked to the head of all WordPress pages automatically.

== Version Notes ==

**v3.04 - 20080921**
* Might have fixed issues in WP2.6
* Most likely fixed your updating Username and Password issues.

**v3.0 - 20080425**
* Ability to browse your SmugMug Galleries through WP2.5's new Insert Image feature in the edit post screen.
* WP Lightbox 2 support for inserted images.
* You no longer have to enter your nickname (if your Username and Password are correct, SmugWP fills it in for you).
* Too many code changes to mention.
* Externalized the SmugWP CSS file.
* A touch more useful.

**v2.0 - 20080409**
* Fully functional Options Page!  Find it at "SmugWP" under the Options tab of your dashboard.  This is the reason for the v2.0 designation.
* Worked the SmugWP variables into the WordPress database (the script has a set of initiating variables used on first-run and for KEY generation).
* Some nifty new footwork for parsing, checking, updating and managing SmugWP variables, fun stuffs.
* Now only checks the content of a single approved page for display (prevents the script from running through every single page).
* Added a Username and Password checker to confirm, upfront, if the Username and Password had been entered correctly or not.
* Function sWP_displayCode(); changed to sWP_displayForm(); for simplicity (function fixed as well).

**v1.1 - 20080408**
* Added readme.txt file.
* Added support for successfully calling sWP_displayCode(); inside a template file.
* Externalized configuration variables in the sWP_config.php file, to allow upgrading of the plugin in its current PHP-only state, without losing modified variables.
* Mistyped variable some how slipped past me.  Fixed.

**v1.0 - 20080407**
* Initial public release.
* Parse all WP content, on all pages, for “<!‒‒ sWP_form ‒‒>” and replace with $sWP_formCode.
* Check all page loads for presence of $_POST[$sWP_req], where $sWP_req is the request ID specified by the user.
* If $_POST[$sWP_req] is present, parses $_POST[$sWP_req] against user specified SmugMug account, and redirects to appropriate gallery if present (if not, amends the form code with error message for display.