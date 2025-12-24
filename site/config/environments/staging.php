<?php

/**
 * Configuration overrides for WP_ENV === 'staging'
 */

use Roots\WPConfig\Config;

/**
 * You should try to keep staging as close to production as possible. However,
 * should you need to, you can always override production configuration values
 * with `Config::define`.
 *
 * Example: `Config::define('WP_DEBUG', true);`
 * Example: `Config::define('DISALLOW_FILE_MODS', false);`
 */

Config::define('DISALLOW_INDEXING', true);


/* Multisite */
Config::define('WP_ALLOW_MULTISITE', env('ALLOW_MULTISITE'));
Config::define('MULTISITE', env('MULTISITE'));
Config::define('SUBDOMAIN_INSTALL', env('SUBDOMAIN_INSTALL')); // Set to true if using subdomains

Config::define('DOMAIN_CURRENT_SITE', env('DOMAIN_CURRENT_SITE'));
Config::define('PATH_CURRENT_SITE', env('PATH_CURRENT_SITE') ?: '/');
Config::define('SITE_ID_CURRENT_SITE', env('SITE_ID_CURRENT_SITE') ?: 1);
Config::define('BLOG_ID_CURRENT_SITE', env('BLOG_ID_CURRENT_SITE') ?: 1);
/**
 * (Optional) Constants to help with assigning custom domains and prevent redirect login loops
 */
Config::define('ADMIN_COOKIE_PATH', '/');
Config::define('COOKIE_DOMAIN', '');
Config::define('COOKIEPATH', '');
Config::define('SITECOOKIEPATH', ''); 


