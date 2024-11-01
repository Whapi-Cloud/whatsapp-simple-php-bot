# Simple WhatsApp Bot in PHP for Beginners

This repository contains a basic WhatsApp bot written in PHP, ideal for developers who are just starting out. The bot responds to various commands with text messages and images. With straightforward setup steps and detailed comments in the code, this bot is designed to help beginners understand API integration with WhatsApp.

## Getting Started

Follow these steps to set up and run the bot:

### Prerequisites

1. **Get Your API Token**
   - Obtain an API token from [Whapi.Cloud](https://whapi.cloud) and place it in `/config/config.php`.

2. **Set Up Webhook URL**
   - Get a webhook URL to receive incoming messages. If you need help with setting up the webhook, refer to our knowledge base article [Where to Find the Webhook URL](https://support.whapi.cloud/help-desk/receiving/webhooks/where-to-find-the-webhook-url).
   - We recommend using a local environment for testing, such as **NGROK**, to expose a local server to the internet.
1. **Download Ngrok** from the official website and extract it.
2. Open the terminal and navigate to the folder where Ngrok is stored.
3. Run `./ngrok http PORT_NUMBER`, replacing `PORT_NUMBER` with the port your Express server is running on locally.
Now you should have a public URL that you can use as a URL for your webhook.
   - Set your webhook URL in the **channel settings** on the Whapi.Cloud dashboard.

3. **Install Composer**
   - Install Composer to manage dependencies.
   ```bash
   php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
   php composer-setup.php
   php -r "unlink('composer-setup.php');"
   ```
4. **Install Dependencies**
   - Install the required dependencies with Composer:
   ```bash
   php composer.phar install
   ```
4. **Run the Bot**
   - Now, your bot is ready to run!
   ```bash
   php server.php
   ```
   
For more detailed instructions on setup and configuration, you can watch our tutorial video on [YouTube](https://youtu.be/1SQ048ZDnE0).

***

## Script Overview

This bot's script contains helpful comments throughout, making it easy to understand the flow and how each function works. Here's a breakdown of the main parts:

### Core Modules

#### `/modules/channel.php`

- **`checkHealth()`** - Checks if the bot's channel is functioning correctly.
- **`sendMessage()`** - Sends a message to the specified recipient.
- **`setWebHook()`** - Sets the webhook URL automatically (this function isn’t used in this example, but is available if you wish to automate webhook settings without dashboard access).
- **`getWebHooks()`** - Retrieves webhook settings from the API.
- **`sendLocalJPG()`** - Converts a local image from the `/images/` directory to base64 format and sends it.

### Main Logic

#### `/src/Whapi.php`

This is where the primary logic of the bot resides. It:
- Filters incoming messages (only non-outgoing messages are processed).
- Extracts the sender's phone number and the text content of the message.
- Ignores non-text messages.
- Uses a switch statement to respond with different messages or images based on the command received.

### Additional Information

Each function is commented to make it easier for beginners to understand how the bot works step by step. Should you have any questions or need further assistance, our support team is available and ready to help.

Happy coding!

