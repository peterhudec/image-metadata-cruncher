=== Image Metadata Cruncher ===
Contributors: PeterHudec
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=RJYHYJJD2VKAN
Tags: image, metadata, EXIF, IPTC, lightroom, photoshop, photomechanic, photostation, meta
Requires at least: 2.7
Tested up to: 3.5
Stable tag: 1.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Gives you ultimate controll over which image metadata WordPress extracts from an uploaded image
and where and in what form it then goes.

== Description ==

When you upload an image in the WordPress admin, WordPress extracts the **EXIF ImageDescription** metadata to the 
**Description** field and the **IPTC Headline** to the **Title** field of
the form that gets displayed after the image has been uploaded.

The **Image Metadata Cruncher** plugin lets you choose what metadata will be extracted from the uploaded image and
in which fields of the form they appear.

Moreover, the plugin's simple templating system allows you to prefill the form with complex strings like
*Image was taken with Canon 7D, exposure was 1/125s and aperture was f/2.8*.

You can even extract metadata to unlimited custom **post meta** that will be saved with the image to the database.

== Installation ==

Copy the **image-metadata-cruncher** folder into the plugins directory and activate.

== Screenshots ==

1. Plugin Settings
2. Available Metadata
3. How to Use Template tags

== Changelog ==

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


