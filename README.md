# Spamir Antibot Redirect

A simple, lightweight, and easy-to-use Link Shortener built with strong antibots to protect your links and keep your mails inboxing!

## Features

-   **Easy to Use**: Simple interface for adding, deleting, and managing short links.
-   **Custom Short Codes**: Use your own custom short codes or let the script generate one for you.
-   **Bot & Threat Protection**: Includes a powerful blocker to prevent bots and other threats from accessing your links.

## A Note on `blocker.php`

Some web hosts, particularly those using cPanel, may have security scanners that flag `blocker.php` during upload. To ensure a smooth installation process, we provide two versions of the blocker file:

-   **`blocker.php`**: This is the primary, encoded version of the file, designed to bypass common security scanners on web hosts. This is the file you should use if you encounter any upload issues.
-   **`blocker-raw.php`**: This file contains the raw, unencoded source code. If your hosting environment allows it and you prefer to use the clean source, you can rename this file to `blocker.php` and upload it.


## Getting Started

### Prerequisites

-   A web server with PHP (7.0+ recommended).
-   That's it! No database required.

### Installation

1.  **Download**: Download the files and upload them to your web server.
2.  **Permissions**: Ensure that the `data/` directory is writable by the web server. This is where your links will be stored.
    ```bash
    chmod -R 775 data/
    ```
3.  **Secure Your Admin Panel**: This is a very important step.
    -   Open the `admin/config.php` file.
    -   Change the default `ADMIN_PASSWORD` to a strong, secret password of your choice.
    ```php
    // in admin/config.php
    define('ADMIN_PASSWORD', 'your-super-secret-and-long-password');
    ```

## How to Use

1.  **Login**: Access the admin panel by navigating to `https://yourdomain.com/admin/`. Enter the password you set in the `config.php` file.
2.  **Add a Link**:
    -   In the "Add New Link" section, enter the long URL you want to shorten.
    -   Optionally, you can provide a custom short code. If you leave it blank, a random one will be generated.
    -   Click "Add Link".
3.  **Manage Links**:
    -   Your existing links are listed in the "Existing Links" table.
    -   You can copy the short URL with the copy button.
    -   You can delete a link by clicking the delete icon.

## How the Blocker Works

The `blocker.php` script provides protection against a wide range of bots and malicious actors. It is included at the top of `index.php` and runs on every request for a short link.

The blocker checks against:
-   Known bot IPs (from `ip.txt`)
-   Known malicious ASNs (from `asn.txt`)
-   Known malicious ISPs (from `isp.txt`)
-   Keywords in the visitor's hostname.
-   A blacklist of IP ranges.

You can customize the blocklists by editing the respective `.txt` files.
