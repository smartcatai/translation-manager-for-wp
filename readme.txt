=== Translation connectors ===
Contributors: arybnikov, dkhapenkov, medic84
Tags: localization, translation, translators, language, multilingual, bilingual, i18n, l10n
Requires at least: 4.8
Tested up to: 5.2.2
Requires PHP: 7.0
Requires PHP extensions: dom, openssl, json
Stable tag: 2.1.3
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Smartcat Translation Manager offers the easiest way to translate your WordPress pages and posts into any language in a few clicks.

== Description ==

[Smartcat](https://www.smartcat.ai/?utm_source=connectors&utm_medium=referral&utm_campaign=wordpress) connects linguists, companies, and agencies to streamline the translation of any content into any language, on demand. Our platform puts your translation process on autopilot, from content creation to payments.

The extension works by linking your WordPress website to a Smartcat account and pushing the content for translation upon request. After the translation is done in Smartcat — either by yourself, your own volunteers, or hired vendors — it is automatically pulled back to WordPress.

== Account & pricing ==

You need to create a Smartcat account as one is not automatically created when installing the extension. To create an account please visit the signup page at [www.smartcat.ai](https://www.smartcat.ai). All translation features in Smartcat are free to use.

== Features ==

- Integrate your WordPress website to a specific Smartcat account
- Choose the translation vendor
- Automatically send new or updated content for translation
- Choose translation workflow stages — translation, editing, proofreading, etc.
- Send content to a specific Smartcat project
- Send content for translation in batches

== Benefits of Smartcat ==

- No document re-formatting is required
- Easy-to-use multilingual translation editor
- Multi-stage translation process — e.g., translation, editing proofreading
- Free collaboration with your own volunteers or coworkers
- [Marketplace](https://www.smartcat.ai/marketplace/?utm_source=connectors&utm_medium=referral&utm_campaign=wordpress) of 250,000+ translators and 2,000+ agencies in 100+ language pairs
- Track progress by language, document, or person
- Automated payments to translation suppliers
- Free support to optimize localization processes

== Installation ==

Please note! For this plugin to work, you need to install the Polylang plugin first.

1. Install and activate [Polylang plugin](https://wordpress.org/plugins/polylang/) on the “Plugins” page
2. Install and activate Smartcat Translation Manager plugin
3. Sign up to [Smartcat](https://www.smartcat.ai/?utm_source=connectors&utm_medium=referral&utm_campaign=wordpress)
4. Go to the [API page](https://smartcat.ai/settings/api/?utm_source=connectors&utm_medium=referral&utm_campaign=wordpress) and generate an API key.
5. At WordPress, go to Localization -> Settings and enter your API credentials.

You’re all set! The world is waiting for your content, so go ahead and translate it in a simple and quick way.

== Support ==

Contact us at support@smartcat.ai with any questions related to:
- Module issues
- Assistance in vendor management (freelancers or LSPs)
- Use of the module for your clients’ needs


== Frequently Asked Questions ==

= I sent my posts to translation but they are shown as “Submitted” and nothing seems to be happening. =

Please wait a few minutes. Data exchange between the plugin and the translation service can take a little time.

== Screenshots ==

1. Translation Connectors Settings.
2. Translation Connectors Dashboard.
3. Translation Connectors Profiles.

== Changelog ==

= 2.1.3 (2019-12-02) =
* Extension checker

= 2.1.2 (2019-10-15) =
* Setting up external cron
* Manual cron executing

= 2.1.0 (2019-08-28) =
* Disable callbacks, setting up cron only

= 2.0.6 (2019-08-21) =
* Callback fix

= 2.0.5 (2019-08-02) =
* Adding shordcodes replacer
* Fix creating project with files
* Minor bug fixes

= 2.0.4 (2019-07-23) =
* Bug fixes with Smartcat projects

= 2.0.3 (2019-07-09) =
* Minor bug fixes

= 2.0.2 (2019-07-05) =
* Fix upgrade function

= 2.0.0 (2019-07-05) =
* Introducing new Profiles feature. Now you can create an unlimited amount of profiles with different settings (languages, workflow stages, etc.) and select a required profile while submitting for translation.
* Debug Mode is now available to investigate any issues that could occur. Turn it on in Localization->Settings and Errors table will appear in Localization section.
* Automatic submission of content implemented. You can find two options in profiles: “Automatically submit new content for translation” and “Automatically submit each content update for translation”. Please note that it works for the Published content only.
* A new Dashboard view with mass and per-row actions.
* General fixes and enhancements.

= 1.2.1 (2019-05-13) =
* Register support form

= 1.2.0 (2019-03-11) =
* Bug fixes
* Using Smartcat Client API v2
* Adding template engine

= 1.1.0 (2019-02-26) =
* A new "Smartcat project" column in the Localization Dashboard with a link leading to the translation project in Smartcat.
* A new feature for translation update that allows you to automatically update completed translation if any changes were made with that project in Smartcat.
* Autoupdate of the Dashboard page.
* Compatibility with Wordpress 5.0.3.
* General bug fixes.

= 1.0.13 (2019-01-23) =
* Auto send on post update
* Fix linebreaks bugs

= 1.0.12 (2019-01-25) =
* Add external tag

= 1.0.11 (2018-12-12) =
* Renaming module

= 1.0.10 (2018-12-06) =
* Fix vendor field

= 1.0.9 (2018-12-03) =
* Fix bugs
* Add debug mode
* Renaming module strings

= 1.0.8 (2018-11-23) =
* Set Project ID input

= 1.0.7 (2017-11-27) =
* Bug fix

= 1.0.6 (2017-10-24) =
* Bug fix - "Invalid username or password" from Smartcat causes a crash

= 1.0.5 (2017-10-24) =
* Bug fix

= 1.0.4 (2017-10-23) =
* Polylang Pro detection fix

= 1.0.3 (2017-10-19) =
* Bug fix

= 1.0.2 (2017-09-02) =
* Bug fix

= 1.0.1 (2017-09-01) =
* Bug fix

= 1.0 =
* First version
