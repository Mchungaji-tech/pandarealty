<?php
/**
 * Panda Realty - Global Settings Loader
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/functions.php';

// Check Maintenance Mode
check_maintenance_redirect();

// Core Brand Settings
$site_name           = get_setting('site_name', 'Panda Realty - Perpetuah Realtor');
$site_tagline        = get_setting('site_tagline', 'Your Eldoret Property Expert | Homes • Land • Investments');
$site_slogan         = get_setting('site_slogan', "We don't just sell property — we change lives");
$contact_phone       = get_setting('contact_phone', '0708 289 852');
$contact_phone_intl  = get_setting('contact_phone_intl', '+254708289852');
$contact_email       = get_setting('contact_email', 'info@pandarealty.co.ke');
$contact_address     = get_setting('contact_address', 'KVDA Plaza, 4th Floor, Oginga Odinga Street, Eldoret, Kenya');
$whatsapp_number     = get_setting('whatsapp_number', '254708289852');
$currency_usd_rate   = (float) get_setting('currency_usd_rate', 130.00);
$two_factor_enforced = get_setting('two_factor_enforced', '0') === '1';
$developer_name      = get_setting('developer_name', 'TekTrend');
$developer_url       = get_setting('developer_url', 'https://tektrend.co.ke');

// Dynamic Logo & Realtor Bio Image
$site_logo           = get_setting('site_logo', get_cms_block('site_logo', ''));
$site_favicon        = get_setting('site_favicon', get_cms_block('site_favicon', ''));
$realtor_image       = get_setting('realtor_image', get_cms_block('realtor_image', 'assets/images/perpetuah.jpg'));

// User preferred currency from cookie/session
$current_currency    = isset($_COOKIE['panda_currency']) ? $_COOKIE['panda_currency'] : (isset($_SESSION['currency']) ? $_SESSION['currency'] : 'KES');

// Current Logged-in User
$current_user = get_current_user_data();

