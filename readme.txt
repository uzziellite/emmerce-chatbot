=== Emmerce Chatbot ===
Contributors: uzziellite
Tags: chatbot, ai, customer support, automation, emmerce
Requires at least: 4.2
Tested up to: 6.7
Stable tag: 1.0.0
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Text Domain: emmerce-chatbot

Adds a professional AI chatbot to your website to manage communication between you and your customers.

== Description ==
Emmerce Chatbot is a powerful WordPress plugin that enhances your website with an intelligent, customizable chatbot. Powered by the Emmerce Backend, it provides automated responses to user inquiries, improving engagement and streamlining customer support. Built with modern technologies like Svelte and Django, it integrates seamlessly into your WordPress site.

### Key Features
- **Automated Responses:** Instant, AI-driven replies to common user questions.
- **Seamless Integration:** Works effortlessly with any WordPress site (version 4.2+).
- **Fast and Efficient:** Optimized for performance.
- **Emmerce Backend:** Enables real-time data processing and scalability.
- **Customizable:** Easily extendable with additional features and settings.

This plugin is ideal for businesses looking to automate customer interactions while maintaining a professional user experience.

== Installation ==

### Standard Installation (Recommended)
1. Download the latest pre-built ZIP file from the [GitHub Releases page](https://github.com/uzziellite/emmerce-chatbot/releases).
2. In your WordPress admin dashboard, go to **Plugins > Add New > Upload Plugin**.
3. Upload the downloaded ZIP file and click **Install Now**.
4. Activate the plugin through the "Plugins" menu.
5. Configure your Emmerce Backend credentials (e.g., API key, username, password) in the plugin settings under the WordPress admin dashboard.

### Developer Setup (Optional)
For those who want to build the plugin from source:
1. Clone the development branch:
   git clone https://github.com/uzziellite/emmerce-chatbot.git
   cd emmerce-chatbot
   git checkout development
2. Install dependencies (requires [Node.js](https://nodejs.org/) v16+ and [Yarn](https://yarnpkg.com/)):
   yarn install
3. Start the development server:
   yarn dev
4. Build for production:
   yarn build
5. Upload the built files to `/wp-content/plugins/emmerce-chatbot` and activate.

== Frequently Asked Questions ==

= What does this plugin do? =
It adds an AI-powered chatbot to your WordPress site, allowing automated communication with visitors and customers.

= Do I need an Emmerce Backend account? =
Yes, you’ll need credentials (e.g., API key) from the Emmerce Backend to enable the chatbot’s full functionality.

= What versions of WordPress are supported? =
The plugin requires WordPress 4.2 or higher and has been tested up to version 6.7.

= Can I customize the chatbot? =
Yes! The plugin is built to be extensible, and developers can modify it using Svelte and Django.

= Do I need to build the plugin myself? =
No, you can download a pre-built ZIP file from the [GitHub Releases page](https://github.com/uzziellite/emmerce-chatbot/releases) and install it directly.

== Screenshots ==
![Emmerce Chatbot](./src/media/bot.png)
![Emmerce Message](./src/media/message.png)
![Emmerce Settings](./src/media/settings.png)

== Changelog ==

= 1.0.0 =
* Initial release of Emmerce Chatbot.

== Upgrade Notice ==

= 1.0.0 =
This is the first version of the plugin. No upgrades available yet.

== Additional Information ==
- **Plugin URI:** [https://emmerce.io](https://emmerce.io)
- **Author URI:** [https://github.com/uzziellite](https://github.com/uzziellite)
- **Support:** For inquiries, contact [uzzielkk@gmail.com](mailto:uzzielkk@gmail.com) or open an issue on [GitHub](https://github.com/uzziellite/emmerce-chatbot).
