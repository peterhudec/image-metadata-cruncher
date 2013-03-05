=== Image Metadata Cruncher ===
Contributors: PeterHudec
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=RJYHYJJD2VKAN
Tags: image, metadata, EXIF, IPTC, lightroom, photoshop, photomechanic, photostation, meta
Requires at least: 2.7
Tested up to: 3.5
Stable tag: 1.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A versatile Swiss Army Knife for extraction and processing of IPTC, EXIF and other image metadata.

== Description ==

A must have tool for photographers who edit their images in **Lightroom** or **Photomechanic** and
don't want to waste their precious time by writing all the **keywords** and **categories**
once again by hand.

WordPress by default extracts the **EXIF ImageDescription** of an uploaded image to
the **Description** field and the **IPTC Headline** to the **Title** field of
the **Edit Media** form.

**Image Metadata Cruncher** gives you ultimate controll over this behaviour.
You decide what metadata gets where and in what form.


Moreover, the plugin's simple but powerfull templating system allows you to
convert the extracted metadata into complex strings like:

> Image was taken with Canon 7D, exposure was 1/125s and aperture was f/2.8.

You can even extract metadata to unlimited custom **post meta** that will be saved with the image to the database.

== Installation ==

Copy the **image-metadata-cruncher** folder into the plugins directory and activate.

== Screenshots ==

1. Plugin Settings
2. Available Metadata
3. How to Use Template tags

== Changelog ==

= 1.7 =
* The syntax highlighting should now work on every browser.
* Fixed a bug when text inserted after the syntax highlighting has been disabled didn't get saved.

= 1.6 =
* Fixed a bug when the **enable highlighting** option didn't get saved.
* Fixed some potential security issues.

= 1.5 =
* Fixed a bug which threw an exif_read_data() warning introduced by previous update.

= 1.4 =
* Added keys **Image:basename**, **Image:filename** and  **Image:extension**.

= 1.3 =
* Fixed broken links to plugin settings in the plugins page.

= 1.2 =
* Fixed a bug when the plugin extracted only the first item of an IPTC metadata of type array like IPTC:Keywords and IPTC:SupplementalCategories

= 1.1 =
* Fixed several bugs
* Added an option to disable syntax highlighting of template tags

= 1.0 =
* Initial version


