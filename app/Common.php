<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

// Force every raw date()/time()/strtotime() call in the app to run in
// Indian Standard Time regardless of the host OS clock (Hostinger's is UTC).
// Without this, attendance/booking timestamps end up 5 hours 30 minutes
// behind reality — e.g. an 11:34 AM punch-in shows as 06:04. Config\App
// alone only affects the framework's Time class, so we set the PHP-wide
// default here at bootstrap.
date_default_timezone_set('Asia/Kolkata');

