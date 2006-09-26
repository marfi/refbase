<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./user_login.php
	// Created:    5-Jan-03, 23:20
	// Modified:   26-Sep-06, 00:40

	// This script manages the login process. It should only be called when the user is not logged in.
	// If the user is logged in, it will redirect back to the calling page.
	// If the user is not logged in, it will show a login <form>.

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'includes/header.inc.php'; // include header
	include 'includes/footer.inc.php'; // include footer
	include 'includes/include.inc.php'; // include common functions
	include 'initialize/ini.inc.php'; // include common variables

	// --------------------------------------------------------------------

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session(true);

	// --------------------------------------------------------------------

	// Initialize preferred display language:
	// (note that 'locales.inc.php' has to be included *after* the call to the 'start_session()' function)
	include 'includes/locales.inc.php'; // include the locales

	// --------------------------------------------------------------------

	if (isset($_REQUEST['referer']))
	{
		$referer = $_REQUEST['referer']; // get the referring URL from the superglobal '$_REQUEST' variable (if any)
	}
	elseif (isset($_SESSION['referer']))
	{
		$referer = $_SESSION['referer']; // get the referring URL from the superglobal '$_SESSION' variable (if any)
	}
	else // if '$referer' is still not set
	{
		if (isset($_SERVER['HTTP_REFERER']))
			$referer = $_SERVER['HTTP_REFERER'];
		else
			$referer = "index.php"; // if all other attempts fail, we'll re-direct to the main page
	}
	
	if (isset($_POST["loginEmail"]))
		$loginEmail = $_POST["loginEmail"];
//		$loginEmail = clean($_POST["loginEmail"], 30); // using the clean function would be secure!

	if (isset($_POST["loginPassword"]))
		$loginPassword = $_POST["loginPassword"];
//		$loginPassword = clean($_POST["loginPassword"], 8); // using the clean function would be secure!

	// Check if the user is already logged in
	if (isset($_SESSION['loginEmail']))
	{
		if (!ereg("error\.php\?.+|user_login\.php|install\.php", $referer))
			header("Location: $referer"); // redirect the user to the calling page
		else
			header("Location: index.php"); // back to main page
	}

	// The user did submit the form but provided none or only one of the two required values: email address AND password:
	if ((isset($loginEmail) && empty($loginEmail)) || (isset($loginPassword) && empty($loginPassword)))
//	if ((empty($_POST["loginEmail"]) && !empty($_POST["loginPassword"])) || (!empty($_POST["loginEmail"]) && empty($_POST["loginPassword"])))
	{		 
		// Save an error message:
		$HeaderString = "<b><span class=\"warning\">In order to login you must supply both, email address and password!</span></b>";

		// Write back session variables:
		saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'
	}

	// Extract the view type requested by the user (either 'Print', 'Web' or ''):
	// ('' will produce the default 'Web' output style)
	if (isset($_REQUEST['viewType']))
		$viewType = $_REQUEST['viewType'];
	else
		$viewType = "";

	// The user did not submit the form -OR- there was an error:
	if (!isset($loginEmail) || !isset($loginPassword) || isset($_SESSION['HeaderString']))
		login_page($referer);
	else
		// The user did submit the form AND provided values to both, email address AND password. Let's check if the info is valid:
		check_login($referer, $loginEmail, $loginPassword);

	// --------------------------------------------------------------------

	function check_login($referer, $loginEmail, $loginPassword)
	{
		global $username;
		global $password;
		global $hostName;
		global $databaseName;
		global $connection;
		global $HeaderString;
		global $loginUserID;
		global $loginFirstName;
		global $loginLastName;
		global $adminLoginEmail;
		global $abbrevInstitution;
		global $tableAuth, $tableUserData, $tableUsers; // defined in 'db.inc.php'

		// Get the two character salt from the email address collected from the challenge
		$salt = substr($loginEmail, 0, 2); 

		// Encrypt the loginPassword collected from the challenge (so that we can compare it to the encrypted passwords that are stored in the 'auth' table)
		$crypted_password = crypt($loginPassword, $salt);

		// CONSTRUCT SQL QUERY:
		$query = "SELECT user_id FROM $tableAuth WHERE email = " . quote_smart($loginEmail) . " AND password = " . quote_smart($crypted_password);

		// -------------------

		// (1) OPEN CONNECTION, (2) SELECT DATABASE
		connectToMySQLDatabase(""); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

		// (3) RUN the query on the database through the connection:
		$result = queryMySQLDatabase($query, ""); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

		// (4) EXTRACT results:
		if (mysql_num_rows($result) == 1) // Interpret query result: Do we have exactly one row?
			{
				$foundUser = true; // then we have found the user
				$row = mysql_fetch_array($result); //fetch the one row into the array $row
			}
		else
			$foundUser = false;

		// -------------------

		if ($foundUser)
		{
			// Clear any other session variables:
			if (isset($_SESSION['errors'])) // delete the 'errors' session variable:
				deleteSessionVariable("errors"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'

			if (isset($_SESSION['formVars'])) // delete the 'formVars' session variable:
				deleteSessionVariable("formVars"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'


			$userID = $row["user_id"]; // extract the user's userID from the last query

			// Now we need to get the user's first name and last name (e.g., in order to display them within the login welcome message)
			$query = "SELECT user_id, first_name, last_name, abbrev_institution, language, last_login FROM $tableUsers WHERE user_id = " . quote_smart($userID); // CONSTRUCT SQL QUERY
	
			// RUN the query on the database through the connection:
			$result = queryMySQLDatabase($query, ""); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

			$row2 = mysql_fetch_array($result); // EXTRACT results: fetch the one row into the array '$row2'

			// Save the fetched user details to the session file:

			// Write back session variables:
			saveSessionVariable("loginEmail", $loginEmail); // function 'saveSessionVariable()' is defined in 'include.inc.php'
			saveSessionVariable("loginUserID", $row2["user_id"]);
			saveSessionVariable("loginFirstName", $row2["first_name"]);
			saveSessionVariable("loginLastName", $row2["last_name"]);
			saveSessionVariable("abbrevInstitution", $row2["abbrev_institution"]);
			saveSessionVariable("userLanguage", $row2["language"]);
			saveSessionVariable("lastLogin", $row2["last_login"]);

			// Get all user groups specified by the current user
			// and (if some groups were found) save them as semicolon-delimited string to the session variable 'userGroups':
			getUserGroups($tableUserData, $row2["user_id"]); // function 'getUserGroups()' is defined in 'include.inc.php'

			if ($loginEmail == $adminLoginEmail) // ('$adminLoginEmail' is specified in 'ini.inc.php')
				// Get all user groups specified by the admin
				// and (if some groups were found) save them as semicolon-delimited string to the session variable 'adminUserGroups':
				getUserGroups($tableUsers, $row2["user_id"]); // function 'getUserGroups()' is defined in 'include.inc.php'

			// Get all user queries that were saved previously by the current user
			// and (if some queries were found) save them as semicolon-delimited string to the session variable 'userQueries':
			getUserQueries($row2["user_id"]); // function 'getUserQueries()' is defined in 'include.inc.php'

			// Get all export formats that were selected previously by the current user
			// and (if some formats were found) save them as semicolon-delimited string to the session variable 'user_export_formats':
			getVisibleUserFormatsStylesTypes($row2["user_id"], "format", "export"); // function 'getVisibleUserFormatsStylesTypes()' is defined in 'include.inc.php'

			// Get all citation formats that were selected previously by the current user
			// and (if some formats were found) save them as semicolon-delimited string to the session variable 'user_cite_formats':
			getVisibleUserFormatsStylesTypes($row2["user_id"], "format", "cite"); // function 'getVisibleUserFormatsStylesTypes()' is defined in 'include.inc.php'

			// Get all citation styles that were selected previously by the current user
			// and (if some styles were found) save them as semicolon-delimited string to the session variable 'user_styles':
			getVisibleUserFormatsStylesTypes($row2["user_id"], "style", ""); // function 'getVisibleUserFormatsStylesTypes()' is defined in 'include.inc.php'

			// Get all document types that were selected previously by the current user
			// and (if some types were found) save them as semicolon-delimited string to the session variable 'user_types':
			getVisibleUserFormatsStylesTypes($row2["user_id"], "type", ""); // function 'getVisibleUserFormatsStylesTypes()' is defined in 'include.inc.php'

			// Get the user permissions for the current user
			// and save all allowed user actions as semicolon-delimited string to the session variable 'user_permissions':
			getPermissions($row2["user_id"], "user", true); // function 'getPermissions()' is defined in 'include.inc.php'


			// We also update the user's entry within the 'users' table:
			$query = "UPDATE $tableUsers SET "
					. "last_login = NOW(), " // set 'last_login' field to the current date & time in 'DATETIME' format (which is 'YYYY-MM-DD HH:MM:SS', e.g.: '2003-12-31 23:45:59')
					. "logins = logins+1 " // increase the number of logins by 1 
					. "WHERE user_id = $userID";

			// RUN the query on the database through the connection:
			$result = queryMySQLDatabase($query, ""); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'


			if (!ereg("error\.php\?.+|user_login\.php|install\.php", $referer))
				header("Location: $referer"); // redirect the user to the calling page
			else
				header("Location: index.php"); // back to main page
		}
		else
		{
		// Ensure loginEmail is not registered, so the user is not logged in
			if (isset($_SESSION['loginEmail'])) // delete the 'loginEmail' session variable:
				deleteSessionVariable("loginEmail"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'

			// Save an error message:
			$HeaderString = "<b><span class=\"warning\">Login failed! You provided an incorrect email address or password.</span></b>";

			// Write back session variables:
			saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'

			login_page($referer);
		}				 

		// -------------------

		// (5) CLOSE the database connection:
		disconnectFromMySQLDatabase(""); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'
	}

	// --------------------------------------------------------------------

	// Function that shows the HTML <form> that is used to collect the email address and password
	function login_page($referer)
	{
		global $HeaderString;
		global $viewType;
		global $loginWelcomeMsg;
		global $loginStatus;
		global $loginLinks;
		global $officialDatabaseName;

		// Show login status (should be logged out!)
		showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

		// If there's no stored message available:
		if (!isset($_SESSION['HeaderString']))
			$HeaderString = "You need to login in order to make any changes to the database:"; // Provide the default welcome message
		else
		{
			$HeaderString = $_SESSION['HeaderString']; // extract 'HeaderString' session variable (only necessary if register globals is OFF!)

			// Note: though we clear the session variable, the current message is still available to this script via '$HeaderString':
			deleteSessionVariable("HeaderString"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
		}

		// Call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
		displayHTMLhead(encodeHTML($officialDatabaseName) . " -- User Login", "index,follow", "User login page. You must be logged in to the " . encodeHTML($officialDatabaseName) . " in order to add, edit or delete records", "", false, "", $viewType, array());
		showPageHeader($HeaderString, "");

		// Build the login form:
		// Note: we use the fact here, that a page can have both, a GET and POST request.
		//       (if you POST, but add ?blah=foo to the end of the action URL, the client will GET, then POST)
?>

<form method="POST" action="user_login.php?referer=<?php echo rawurlencode($referer); ?>">
<table align="center" border="0" cellpadding="2" cellspacing="5" width="95%" summary="This table holds a login form for the <?php echo encodeHTML($officialDatabaseName); ?>">
	<tr>
		<td width="174" valign="bottom">
			<b>Email Address:</b>
		</td>
		<td valign="bottom">
			<input type="text" name="loginEmail" size="30">
		</td>
	</tr>
	<tr>
		<td valign="bottom">
			<b>Password:</b>
		</td>
		<td valign="bottom">
			<input type="password" name="loginPassword" size="30">
		</td>
	</tr>
	<tr>
		<td valign="bottom">
			&nbsp;
		</td>
		<td valign="bottom">
			<input type="submit" value="Login">
		</td>
	</tr>
</table>
</form><?php
	}

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'showPageFooter()' and 'displayHTMLfoot()' functions (which are defined in 'footer.inc.php')
	showPageFooter($HeaderString, "");

	displayHTMLfoot();

	// --------------------------------------------------------------------
?>
