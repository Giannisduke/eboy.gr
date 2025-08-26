<?php

/**
 * Configuration overrides for WP_ENV === 'development'
 */

use Roots\WPConfig\Config;

use function Env\env;

Config::define('SAVEQUERIES', true);
Config::define('WP_DEBUG', true);
Config::define('WP_DEBUG_DISPLAY', true);
Config::define('WP_DEBUG_LOG', env('WP_DEBUG_LOG') ?? true);
Config::define('WP_DISABLE_FATAL_ERROR_HANDLER', true);
Config::define('SCRIPT_DEBUG', true);
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

ini_set('display_errors', '1');

// Enable plugin and theme updates and installation from the admin
Config::define('DISALLOW_FILE_MODS', false);
