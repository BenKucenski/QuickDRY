<?php
/**
 * PHP LDAP CLASS FOR MANIPULATING ACTIVE DIRECTORY
 * Version 3.2
 *
 * PHP Version 5 with SSL and LDAP support
 *
 * Written by Scott Barnett, Richard Hyland
 *   email: scott@wiggumworld.com, adldap@richardhyland.com
 *   http://adldap.sourceforge.net/
 *
 * Copyright (c) 2006-2009 Scott Barnett, Richard Hyland
 *
 * We'd appreciate any improvements or additions to be submitted back
 * to benefit the entire community :)
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * @category ToolsAndUtilities
 * @package adLDAP
 * @author Scott Barnett, Richard Hyland
 * @copyright (c) 2006-2009 Scott Barnett, Richard Hyland
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPLv2.1
 * @revision $Revision: 55 $
 * @version 3.2
 * @link http://adldap.sourceforge.net/
 */
// requires : extension=php_ldap.dll
/**
 * Define the different types of account in AD
 */
const ADLDAP_NORMAL_ACCOUNT = 805306368;
const ADLDAP_WORKSTATION_TRUST = 805306369;
const ADLDAP_INTERDOMAIN_TRUST = 805306370;
const ADLDAP_SECURITY_GLOBAL_GROUP = 268435456;
const ADLDAP_DISTRIBUTION_GROUP = 268435457;
const ADLDAP_SECURITY_LOCAL_GROUP = 536870912;
const ADLDAP_DISTRIBUTION_LOCAL_GROUP = 536870913;
const ADLDAP_FOLDER = 'OU';
const ADLDAP_CONTAINER = 'CN';

require_once 'adLDAP/adLDAP.php';
require_once 'adLDAP/adLDAPException.php';




