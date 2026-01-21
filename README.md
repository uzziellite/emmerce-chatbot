# Emmerce Chatbot

This wordpress plugin can be accessed at https://github.com/uzziellite/emmerce-chatbot

A smart, customizable chatbot for WordPress powered by Emmerce Backend.

## Overview

The Emmerce Chatbot is a WordPress plugin designed to boost user engagement with intelligent, automated responses. Built with a modern tech stack and integrated with the Emmerce Backend, it seamlessly embeds into your WordPress site to handle inquiries and provide dynamic, real-time interactions.

## Features

- 💬 **Automated Responses:** Delivers instant, AI-driven replies to common user questions.
- 🔗 **Seamless Integration:** Embeds effortlessly into any WordPress site.
- ⚡ **Fast and Efficient:** Optimized with Yarn for quick setup and performance.
- 🔄 **Emmerce Backend:** Powers real-time data processing and scalability.
- 🛠️ **Customizable:** Easily extensible with additional features and settings.

## Technology Stack

- **Frontend:** JavaScript - Svelte (managed with Yarn)
- **Platform:** WordPress (6.0 or higher recommended)
- **API Communication:** REST API for data exchange, WebSockets for real-time updates

## Installation & Setup

### Prerequisites

Ensure you have the following installed:
- [Node.js](https://nodejs.org/) (v22+ recommended)
- [Yarn](https://yarnpkg.com/)
- A running WordPress site (v6.0+ recommended)

### Clone the Repository Development Branch

```sh
git clone https://github.com/uzziellite/emmerce-chatbot.git
cd emmerce-chatbot
git checkout development
```

### Install Dependencies

```sh
yarn install
```

### Configure Environment Variables

Install the Emmerce Chatbot plugin via the WordPress admin dashboard.

Navigate to the plugin settings and add your Emmerce API key

### Start the Development Server

```sh
yarn dev
```

This launches a local development server to test the chatbot frontend.

## Deployment

Build for Production
Compile the plugin for production:

```sh
yarn build
```

Upload the generated files to your WordPress plugins directory (wp-content/plugins/emmerce-chatbot).

Activate the plugin from the WordPress admin dashboard.

## Contributing

Contributions are welcome! To contribute:

- Fork the repository.

- Create a feature branch (git checkout -b feature-name).

- Commit your changes (git commit -m "Added new feature").

- Push to your branch (git push origin feature-name).

- Open a Pull Request.

## License

This project is licensed under the GNU General Public License v3.
Contact
For support or inquiries, reach out to uzzielkk@gmail.com (mailto:uzzielkk@gmail.com) or open an issue in the repository.
