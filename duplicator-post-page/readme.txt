=== Quick Copy – Duplicate & Clone Posts & Pages ===
Contributors: iqbal1hossain
Tags: clone page, copy posts, duplicate pages, duplicate post, duplicate posts
Requires at least: 6.1
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.3.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Easily duplicate or clone any post or page, including all metadata and taxonomies, with just one click.

== Description ==

**Quick Copy – Duplicate Posts & Pages** is a simple yet powerful WordPress plugin that allows you to easily duplicate any post or page, including all associated metadata and taxonomies, with just a single click. This tool is ideal for users who frequently need to clone existing content, making it quicker to create similar posts or pages without the hassle of manually copying and pasting content.

Whether you're managing a blog, running a website, or developing for clients, the **Duplicator Post Page** plugin simplifies content duplication, saving you time and ensuring all relevant data is retained.

### 🚀 Key Benefits

* Duplicate Posts and Pages: Create an exact copy of any post or page with just one click.
* Retain Metadata: All custom fields and metadata from the original post or page are duplicated to the new version.
* Retain Taxonomies: Categories, tags, and custom taxonomies are also copied along with the content.
* Quick Access: Easily accessible "Duplicate" link available in the post and page action links within the WordPress admin interface.
* Draft Status: The duplicated post or page is saved as a draft, allowing you to make further changes before publishing.
* Clone Post & Clone Page: Clone any post or page in one click and reuse it as a template for new content.
* Post Duplicator & Page Duplicator: A simple WP duplicator for posts and pages, with no settings to configure.
* Featured Image & Custom Fields: The featured image, excerpt, custom fields and page builder data are copied too.
* Keeps Page Structure: Page parent, menu order, comment status and ping status are carried over to the copy.
* Page Builder Friendly: Clone pages built with Elementor, Gutenberg, Divi, Beaver Builder, Bricks and more.
* AI Agent Ready: Supports the WordPress Abilities API, so AI agents can duplicate posts and pages (WordPress 6.9+).
* Lightweight: No extra database tables, no bloat, and no impact on your site's front end.

### 📋 How to Duplicate a Post or Page

1. Go to **Posts > All Posts** or **Pages > All Pages**.
2. Hover over the post or page you want to copy.
3. Click **Duplicate**.
4. A new draft named "Your Title (Copy)" is created. Edit it and publish when ready.

### 💡 Popular Uses

* **Duplicate post** – copy a blog post to use as a starting template for a new article.
* **Duplicate page** – copy a landing page, sales page or service page layout.
* **Clone page** – reuse an Elementor or block editor design without rebuilding it.
* **Clone post** – create several similar posts (products, events, reviews) quickly.
* **Post page duplicate** – keep a safe backup copy of a post or page before making big edits.

Whether you're managing a large content library or building websites for clients, **Quick Copy** makes duplication simple, efficient, and reliable.

📺 **Watch how it works:**
[https://www.youtube.com/watch?v=GzBJW-NE1l8](https://www.youtube.com/watch?v=GzBJW-NE1l8)

---

### 🌟 You May Also Like

Looking to build stunning websites **faster with Feed**?

Ohara – Feed Builder For YouTube

---

== Compatibility ==

**Quick Copy – Duplicate Posts & Pages** is designed to work seamlessly with most modern WordPress plugins, themes, and page builders. When duplicating content, the plugin preserves post content, custom fields, metadata, and taxonomies, helping maintain compatibility across your website.

=== Popular Page Builders ===

* Elementor
* Gutenberg (Block Editor)
* Spectra – WordPress Gutenberg Blocks
* Essential Addons for Elementor
* Beaver Builder
* Divi Builder
* WPBakery Page Builder
* Bricks Builder
* Oxygen Builder
* Breakdance Builder
* Gutenberg Essential Blocks

---

== Installation ==

1. Install the plugin directly from the WordPress plugin repository or upload the `duplicator-post-page` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to your **Posts** or **Pages** list, hover over an item, and click the **Duplicate** link.

That’s it — your new copy will be created instantly as a draft!

---

== External services ==

WPShark news is OFF by default. Nothing is shown and no request is made until an administrator enables it with the "Enable WPShark news" link under the plugin on the Plugins screen (the same link disables it again).

When enabled, a "WPShark News" dashboard widget lists WPShark blog posts and tips, and your site requests https://wpshark.xyz/wp-json/wpshark-hub/v1/feed about every 6 hours. Quick Copy never shows banners or notices from this feed. The request contains no personal data, site URL, or list of plugins; like any web request it reveals your server's IP address to wpshark.xyz. Images shown in the widget are loaded from wpshark.xyz by the administrator's browser.

Service provider: WPShark — https://wpshark.xyz/
Terms of use: https://wpshark.xyz/terms/
Privacy policy: https://wpshark.xyz/privacy-policy/

---

== Frequently Asked Questions ==

= What happens to the duplicated post or page? =
The duplicated post or page is saved as a draft with "(Copy)" appended to the title.

= How do I clone a post or page? =
Hover over the post or page in your Posts or Pages list and click "Duplicate". A copy is saved as a draft.

= How do I duplicate a page in WordPress? =
Go to Pages > All Pages, hover over the page and click "Duplicate". The new page keeps its content, parent, custom fields and page builder layout.

= How do I duplicate a post in WordPress? =
Go to Posts > All Posts, hover over the post and click "Duplicate". The new post keeps its categories, tags, featured image and custom fields.

= Can I clone a page built with Elementor or another page builder? =
Yes. Page builder layouts are stored in custom fields, and Quick Copy copies all custom fields, so the cloned page keeps its design.

= Is the featured image copied? =
Yes. The featured image is copied to the duplicate along with the other metadata.

= Will a duplicate post or page affect my SEO? =
No. Duplicates are saved as drafts, so they are not public. Change the title, slug and content before publishing to avoid duplicate content.

= Is this the same as the Duplicator backup plugin? =
No. Quick Copy duplicates and clones individual posts and pages inside your site. It does not back up or migrate a whole website.

= Can I duplicate custom post types? =
Currently, the plugin supports duplicating posts and pages. Custom post-type support may be added in future updates.

= Will the duplicated post or page retain all metadata and taxonomies? =
Yes, the duplicated post or page will retain all metadata and taxonomies associated with the original.

---

== Changelog ==

= 1.3.5 =
* Improved: Readme updates with clearer duplicate post, duplicate page, clone post and clone page instructions.
* Added: New FAQ entries and a "How to Duplicate a Post or Page" guide.

= 1.3.4 =
* Added: Optional "WPShark News" dashboard widget, off by default. Enable or disable it with the "Enable WPShark news" link on the Plugins screen.
* Added: Uninstall cleanup of plugin options.
* Changed: Minimum PHP version is now 7.4.

= 1.3.0 =
* Added: WordPress Abilities API support, so AI agents can duplicate posts and pages. Registers `duplicator-post-page/duplicate-post` and `duplicator-post-page/list-duplicatable-posts` under the "Content Duplication" category. Requires WordPress 6.9 or later; earlier versions are unaffected.
* Fixed: Custom fields holding arrays or objects were silently copied as empty values.
* Fixed: The edit lock was copied to the duplicate, which could show a false "currently editing" warning.
* Improved: Duplicates now also carry over the excerpt, parent, menu order, comment status and ping status.

= 1.2.26 =
* Improved: Readme optimization and better plugin discovery in WordPress search.

= 1.1.2 =
* Improved: Minor bug fixes and UI improvements.

= 1.1.1 =
* Security: Enhanced validation and sanitization.

= 1.0.3 =
* Added: Custom row meta in the plugin list for easier access.

= 1.0.0 =
* Initial release of Quick Copy.
* Added one-click duplication for posts and pages, including all metadata and taxonomies.

---

== Screenshots ==

1. "Duplicate" link under each post or page.
2. Duplicated post saved as draft.
3. Plugin settings and upgrade option.

---

== License ==

This plugin is licensed under the **GPLv2 or later**.
See the [License URI](https://www.gnu.org/licenses/gpl-2.0.html) for more information.