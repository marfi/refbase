<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./user_receipt.php
	// Created:    16-Apr-02, 10:54
	// Modified:   10-Jun-06, 23:00

	// This script shows the user a receipt for their user UPDATE or INSERT.
	// It carries out no database actions and can be bookmarked.
	// The user must be logged in to view it.

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

	// Extract the 'userID' parameter from the request:
	if (isset($_REQUEST['userID']))
		$userID = $_REQUEST['userID'];
	else
		$userID = ""; // we do it for clarity reasons here (and in order to prevent any 'Undefined variable...' messages)

	// Check if the user is logged in
	if (!isset($_SESSION['loginEmail']) && ($userID != -1))
	// Note: 'user_validation.php' uses the non-existing user ID '-1' as trigger to show the email notification receipt page (instead of the standard receipt page)
	{
		// save an error message:
		$HeaderString = "<b><span class=\"warning\">You must login to view your user account details and options!</span></b>";

		// save the URL of the currently displayed page:
		$referer = $_SERVER['HTTP_REFERER'];

		// Write back session variables:
		saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'
		saveSessionVariable("referer", $referer);

		header("Location: user_login.php");
		exit;
	}

	// Check the correct parameters have been passed
	if ($userID == "")
	{
		// save an error message:
		$HeaderString = "<b><span class=\"warning\">Incorrect parameters to script 'user_receipt.php'!</span></b>";

		// Write back session variables:
		saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'

		// Redirect the browser back to the calling page
		header("Location: index.php"); // Note: if 'header("Location: " . $_SERVER['HTTP_REFERER'])' is used, the error message won't get displayed! ?:-/
		exit;
	}

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE
	connectToMySQLDatabase(""); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// For regular users, validate that the correct userID has been passed to the script:
 	if (isset($_SESSION['loginEmail']) && ($loginEmail != $adminLoginEmail))
		// check this user matches the userID (viewing user account details is only allowed to the admin)
		if ($userID != getUserID($loginEmail))
		{
			// otherwise save an error message:
			$HeaderString = "<b><span class=\"warning\">You can only view your own user receipt!<span></b>";

			// Write back session variables:
			saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'

			$userID = getUserID($loginEmail); // and re-establish the user's correct user_id
		}

	// Extract the type of action requested by the user, either 'delete' or ''.
	// ('' or anything else will be treated equal to 'edit').
	// We actually extract the variable 'userAction' only if the admin is logged in
	// (since only the admin will be allowed to delete a user):
 	if (isset($_SESSION['loginEmail']) && ($loginEmail == $adminLoginEmail)) // ('$adminLoginEmail' is specified in 'ini.inc.php')
 	{
		if (isset($_REQUEST['userAction']))
			$userAction = $_REQUEST['userAction'];
		else
			$userAction = ""; // we do it for clarity reasons here (and in order to prevent any 'Undefined variable...' messages)

		if ($userAction == "Delete")
		{
			if ($userID == getUserID($loginEmail)) // if the admin userID was passed to the script
			{
				// save an error message:
				$HeaderString = "<b><span class=\"warning\">You cannot delete your own user data!<span></b>";

				// Write back session variables:
				saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'

				$userAction = "Edit"; // and re-set the user action to 'edit'
			}
		}
		else
			$userAction = "Edit"; // everything that isn't a 'delete' action will be an 'edit' action
	}
	else // otherwise we simply assume an 'edit' action, no matter what was passed to the script (thus, no regular user will be able to delete a user)
		$userAction = "Edit";

	// Extract the view type requested by the user (either 'Print', 'Web' or ''):
	// ('' will produce the default 'Web' output style)
	if (isset($_REQUEST['viewType']))
		$viewType = $_REQUEST['viewType'];
	else
		$viewType = "";

	// --------------------------------------------------------------------

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

	// Show the user confirmation:
	if ($userID == -1) // 'userID=-1' is sent by 'user_validation.php' to indicate a NEW user who has successfully submitted 'user_details.php'
		showEmailConfirmation($userID);
	else
		showUserData($userID, $userAction, $connection);

	// ----------------------------------------------

	// (5) CLOSE the database connection:
	disconnectFromMySQLDatabase(""); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// Show a new user a confirmation screen, confirming that the submitted user data have been correctly received:
	function showEmailConfirmation($userID)
	{
		global $HeaderString;
		global $viewType;
		global $loginWelcomeMsg;
		global $loginStatus;
		global $loginLinks;
		global $loginEmail;
		global $adminLoginEmail;
		global $officialDatabaseName;

		// Build the correct header message:
		if (!isset($_SESSION['HeaderString']))
			$HeaderString = "Submission confirmation:"; // provide the default message
		else
		{
			$HeaderString = $_SESSION['HeaderString']; // extract 'HeaderString' session variable (only necessary if register globals is OFF!)

			// Note: though we clear the session variable, the current message is still available to this script via '$HeaderString':
			deleteSessionVariable("HeaderString"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
		}

		// Call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
		displayHTMLhead(encodeHTML($officialDatabaseName) . " -- User Receipt", "noindex,nofollow", "Receipt page confirming correct submission of new user details to the " . encodeHTML($officialDatabaseName), "", false, "", $viewType, array());
		showPageHeader($HeaderString, "");

		$confirmationText = "Thanks for your interest in the " . encodeHTML($officialDatabaseName) . "!"
					. "<br><br>The data you provided have been sent to our database admin."
					. "<br>We'll process your request and mail back to you as soon as we can!"
					. "<br><br>[Back to <a href=\"index.php\">" . encodeHTML($officialDatabaseName) . " Home</a>]";

		// Start a table:
		echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table displays user submission feedback\">";

		echo "\n<tr>\n\t<td>" . $confirmationText . "</td>\n</tr>";

		echo "\n</table>";

	}

	// --------------------------------------------------------------------

	// Show the user an UPDATE receipt:
	// (if the admin is logged in, this function will also provide a 'new user INSERT' receipt)
	function showUserData($userID, $userAction, $connection)
	{
		global $HeaderString;
		global $viewType;
		global $loginWelcomeMsg;
		global $loginStatus;
		global $loginLinks;
		global $loginEmail;
		global $adminLoginEmail;
		global $officialDatabaseName;
		global $defaultLanguage;
		global $tableUsers; // defined in 'db.inc.php'

		// CONSTRUCT SQL QUERY:
		$query = "SELECT * FROM $tableUsers WHERE user_id = $userID";

		// (3) RUN the query on the database through the connection:
		$result = queryMySQLDatabase($query, ""); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

		// (4) EXTRACT results (since 'user_id' is the unique primary key for the 'users' table, there will be only one matching row)
		$row = @ mysql_fetch_array($result);

		// Build the correct header message:
		if (!isset($_SESSION['HeaderString'])) // if there's no saved message
			if ($userAction == "Delete") // provide an appropriate header message:
				$HeaderString = "<b><span class=\"warning\">Delete user</span> " . encodeHTML($row["first_name"]) . " " . encodeHTML($row["last_name"]) . " (" . $row["email"] . ")</b>:";
			elseif (empty($userID))
				$HeaderString = "Account details and options for anyone who isn't logged in:";
			else // provide the default message:
				$HeaderString = "Account details and options for <b>" . encodeHTML($row["first_name"]) . " " . encodeHTML($row["last_name"]) . " (" . $row["email"] . ")</b>:";
		else
		{
			$HeaderString = $_SESSION['HeaderString']; // extract 'HeaderString' session variable (only necessary if register globals is OFF!)

			// Note: though we clear the session variable, the current message is still available to this script via '$HeaderString':
			deleteSessionVariable("HeaderString"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
		}

		// Call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
		displayHTMLhead(encodeHTML($officialDatabaseName) . " -- User Receipt", "noindex,nofollow", "Receipt page confirming correct entry of user details and options for the " . encodeHTML($officialDatabaseName), "", false, "", $viewType, array());
		showPageHeader($HeaderString, "");

		// Start main table:
		echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table displays user account details and options\">";

			echo "\n<tr>"
				. "\n\t<td valign=\"top\" width=\"28%\">";

			// Start left sub-table:
			echo "\n\t\t<table border=\"0\" cellpadding=\"0\" cellspacing=\"10\" summary=\"User account details\">";

			echo "\n\t\t<tr>\n\t\t\t<th align=\"left\" class=\"smaller\">Account Details:</th>\n\t\t</tr>";

			if (mysql_num_rows($result) == 1) // If there's a user associated with this user ID
			{
				// Display a password reminder:
				// (but only if a normal user is logged in -OR- the admin is logged in AND the updated user data are his own!)
				if (($loginEmail != $adminLoginEmail) | (($loginEmail == $adminLoginEmail) && ($userID == getUserID($loginEmail))))
					echo "\n\t\t<tr>\n\t\t\t<td><i>Please record your password somewhere safe for future use!</i></td>\n\t\t</tr>";
		
				// Print title, first name, last name and institutional abbreviation:
				echo "\n\t\t<tr>\n\t\t\t<td>\n\t\t\t\t";
				if (!empty($row["title"]))
					echo $row["title"] . ". ";
				echo encodeHTML($row["first_name"]) . " " . encodeHTML($row["last_name"]) . " (" . encodeHTML($row["abbrev_institution"]) . ")"; // Since the first name, last name and abbrev. institution fields are mandatory, we don't need to check if they're empty
		
				// Print institution name:
				if (!empty($row["institution"]))
					echo "\n\t\t\t\t<br>\n\t\t\t\t" . encodeHTML($row["institution"]);
		
				// Print corporate institution name:
				if (!empty($row["corporate_institution"]))
					echo "\n\t\t\t\t<br>\n\t\t\t\t" . encodeHTML($row["corporate_institution"]);
		
				// If any of the address lines contain data, add a spacer row:
				if (!empty($row["address_line_1"]) || !empty($row["address_line_2"]) || !empty($row["address_line_3"]) || !empty($row["zip_code"]) || !empty($row["city"]) || !empty($row["state"]) || !empty($row["country"]))
					echo "\n\t\t\t\t<br>";
		
				// Print first address line:
				if (!empty($row["address_line_1"]))
					echo "\n\t\t\t\t<br>\n\t\t\t\t" . encodeHTML($row["address_line_1"]);
		
				// Print second address line:
				if (!empty($row["address_line_2"]))
					echo "\n\t\t\t\t<br>\n\t\t\t\t" . encodeHTML($row["address_line_2"]);
		
				// Print third address line:
				if (!empty($row["address_line_3"]))
					echo "\n\t\t\t\t<br>\n\t\t\t\t" . encodeHTML($row["address_line_3"]);
		
				// Print zip code and city:
				if (!empty($row["zip_code"]) && !empty($row["city"])) // both fields are available
					echo "\n\t\t\t\t<br>\n\t\t\t\t" . encodeHTML($row["zip_code"]) . " " . encodeHTML($row["city"]);
				elseif (!empty($row["zip_code"]) && empty($row["city"])) // only 'zip_code' available
					echo "\n\t\t\t\t<br>\n\t\t\t\t" . encodeHTML($row["zip_code"]);
				elseif (empty($row["zip_code"]) && !empty($row["city"])) // only 'city' field available
					echo "\n\t\t\t\t<br>\n\t\t\t\t" . encodeHTML($row["city"]);
		
				// Print state:
				if (!empty($row["state"]))
					echo "\n\t\t\t\t<br>\n\t\t\t\t" . encodeHTML($row["state"]);
		
				// Print country:
				if (!empty($row["country"]))
					echo "\n\t\t\t\t<br>\n\t\t\t\t" . encodeHTML($row["country"]);
		
				// If any of the phone/url/email fields contain data, add a spacer row:
				if (!empty($row["phone"]) || !empty($row["url"]) || !empty($row["email"]))
					echo "\n\t\t\t\t<br>";
		
				// Print phone number:
				if (!empty($row["phone"]))
					echo "\n\t\t\t\t<br>\n\t\t\t\t" . "Phone: " . encodeHTML($row["phone"]);
		
				// Print URL:
				if (!empty($row["url"]))
					echo "\n\t\t\t\t<br>\n\t\t\t\t" . "URL: <a href=\"" . $row["url"] . "\">" . $row["url"] . "</a>";
		
				// Print email:
					echo "\n\t\t\t\t<br>\n\t\t\t\t" . "Email: <a href=\"mailto:" . $row["email"] . "\">" . $row["email"] . "</a>"; // Since the email field is mandatory, we don't need to check if it's empty
	
				echo "\n\t\t\t</td>\n\t\t</tr>";
	
				// If the admin is logged in, allow the display of a button that will delete the currently shown user:
				if (isset($_SESSION['loginEmail']) && ($loginEmail == $adminLoginEmail)) // ('$adminLoginEmail' is specified in 'ini.inc.php')
				{
					if ($userAction == "Delete")
						echo "\n\t\t<tr>"
							. "\n\t\t\t<td>"
							. "\n\t\t\t\t<form action=\"user_removal.php\" method=\"POST\">"
							. "\n\t\t\t\t\t<input type=\"hidden\" name=\"userID\" value=\"" . $userID . "\">"
							. "\n\t\t\t\t\t<input type=\"submit\" value=\"" . $userAction . " User\">"
							. "\n\t\t\t\t</form>"
							. "\n\t\t\t</td>"
							. "\n\t\t</tr>";
				}
	
				if ($userAction != "Delete")
					echo "\n\t\t<tr>"
						. "\n\t\t\t<td>"
						. "\n\t\t\t\t<form action=\"user_details.php\" method=\"POST\">"
						. "\n\t\t\t\t\t<input type=\"hidden\" name=\"userID\" value=\"" . $userID . "\">"
						. "\n\t\t\t\t\t<input type=\"submit\" value=\"" . $userAction . " Details\">"
						. "\n\t\t\t\t</form>"
						. "\n\t\t\t</td>"
						. "\n\t\t</tr>";
			}
			else // no user exists with this user ID
			{
				echo "\n\t\t<tr>\n\t\t\t<td>(none)</td>\n\t\t</tr>";
			}

			// Close left sub-table:
			echo "\n\t\t</table>";
	
			// Close left table cell of main table:
			echo "\n\t</td>";

			// ------------------------------------------------------------

			// Start right table cell of main table:
			echo "\n\t<td valign=\"top\">";

			// Start right sub-table:
			echo "\n\t\t<table border=\"0\" cellpadding=\"0\" cellspacing=\"10\" summary=\"User account options\">";

			echo "\n\t\t<tr>\n\t\t\t<th align=\"left\" class=\"smaller\" colspan=\"2\">Display Options:</th>\n\t\t</tr>";

			echo "\n\t\t<tr valign=\"top\">"
				. "\n\t\t\t<td>Use language:</td>";

			if (mysql_num_rows($result) == 1) // If there's a user associated with this user ID
				echo "\n\t\t\t<td>\n\t\t\t\t<ul type=\"none\" class=\"smallup\">\n\t\t\t\t\t<li>" . $row["language"] . "</li>\n\t\t\t\t</ul>\n\t\t\t</td>";
			else // no user exists with this user ID
				echo "\n\t\t\t<td>\n\t\t\t\t<ul type=\"none\" class=\"smallup\">\n\t\t\t\t\t<li>" . $defaultLanguage . "</li>\n\t\t\t\t</ul>\n\t\t\t</td>";

			echo "\n\t\t</tr>";

			if ($loginEmail == $adminLoginEmail) // if the admin is logged in
			{
				$ShowEnabledDescriptor = "Enabled";
	
				// get all formats/styles/types that are available and were enabled by the admin for the current user:
				$userTypesArray = getEnabledUserFormatsStylesTypes($userID, "type", "", false); // function 'getEnabledUserFormatsStylesTypes()' is defined in 'include.inc.php'
	
				$citationStylesArray = getEnabledUserFormatsStylesTypes($userID, "style", "", false);
			
				$citationFormatsArray = getEnabledUserFormatsStylesTypes($userID, "format", "cite", false);
			
				$exportFormatsArray = getEnabledUserFormatsStylesTypes($userID, "format", "export", false);
			}
			else // if a normal user is logged in
			{
				$ShowEnabledDescriptor = "Show";
	
				// get all formats/styles/types that were selected by the current user
				// and (if some formats/styles/types were found) save them as semicolon-delimited string to an appropriate session variable:
				$userTypesArray = getVisibleUserFormatsStylesTypes($userID, "type", ""); // function 'getVisibleUserFormatsStylesTypes()' is defined in 'include.inc.php'
	
				$citationStylesArray = getVisibleUserFormatsStylesTypes($userID, "style", "");
			
				$citationFormatsArray = getVisibleUserFormatsStylesTypes($userID, "format", "cite");
	
				$exportFormatsArray = getVisibleUserFormatsStylesTypes($userID, "format", "export");
	
				// Note: the function 'getVisibleUserFormatsStylesTypes()' will only update the appropriate session variables if
				//       either a normal user is logged in -OR- the admin is logged in AND the updated user data are his own(*);
				//       otherwise, the function will simply return an array containing all matching values
				//       (*) the admin-condition won't apply here, though, since this function gets only called for normal users. This means, that
				//           the admin is currently not able to hide any items from his popup lists via the admin interface (he'll need to hack the MySQL tables)!
			}
		
			echo "\n\t\t<tr valign=\"top\">"
				. "\n\t\t\t<td>" . $ShowEnabledDescriptor . " reference types:</td>" // list types
				. "\n\t\t\t<td>\n\t\t\t\t<ul type=\"none\" class=\"smallup\">\n\t\t\t\t\t<li>";

			if (empty($userTypesArray))
				echo "(none)";
			else
				echo implode("</li>\n\t\t\t\t\t<li>", $userTypesArray);

			echo "</li>\n\t\t\t\t</ul>\n\t\t\t</td>"
				. "\n\t\t</tr>";


			echo "\n\t\t<tr valign=\"top\">"
				. "\n\t\t\t<td>" . $ShowEnabledDescriptor . " citation styles:</td>" // list styles
				. "\n\t\t\t<td>\n\t\t\t\t<ul type=\"none\" class=\"smallup\">\n\t\t\t\t\t<li>";

			if (empty($citationStylesArray))
				echo "(none)";
			else
				echo implode("</li>\n\t\t\t\t\t<li>", $citationStylesArray);

			echo "</li>\n\t\t\t\t</ul>\n\t\t\t</td>"
				. "\n\t\t</tr>";


			echo "\n\t\t<tr valign=\"top\">"
				. "\n\t\t\t<td>" . $ShowEnabledDescriptor . " citation formats:</td>" // list cite formats
				. "\n\t\t\t<td>\n\t\t\t\t<ul type=\"none\" class=\"smallup\">\n\t\t\t\t\t<li>";

			if (empty($citationFormatsArray))
				echo "(none)";
			else
				echo implode("</li>\n\t\t\t\t\t<li>", $citationFormatsArray);

			echo "</li>\n\t\t\t\t</ul>\n\t\t\t</td>"
				. "\n\t\t</tr>";


			echo "\n\t\t<tr valign=\"top\">"
				. "\n\t\t\t<td>" . $ShowEnabledDescriptor . " export formats:</td>" // list export formats
				. "\n\t\t\t<td>\n\t\t\t\t<ul type=\"none\" class=\"smallup\">\n\t\t\t\t\t<li>";

			if (empty($exportFormatsArray))
				echo "(none)";
			else
				echo implode("</li>\n\t\t\t\t\t<li>", $exportFormatsArray);

			echo "</li>\n\t\t\t\t</ul>\n\t\t\t</td>"
				. "\n\t\t</tr>";

			if ($loginEmail == $adminLoginEmail) // if the admin is logged in
			{
				// get all user permissions for the current user:
				$userPermissionsArray = getPermissions($userID, "user", false); // function 'getPermissions()' is defined in 'include.inc.php'

				$enabledUserActionsArray = array(); // initialize array variables
				$disabledUserActionsArray = array();

				// separate enabled permission settings from disabled ones:
				foreach($userPermissionsArray as $permissionKey => $permissionValue)
					if ($permissionValue == 'yes')
						$enabledUserActionsArray[] = $permissionKey; // append this field's permission name (as value) to the array of enabled user actions
					else
						$disabledUserActionsArray[] = $permissionKey; // append this field's permission name (as value) to the array of disabled user actions

				// convert the raw field names from table 'user_permissions' into somewhat more readable names:
				$searchReplaceActionsArray = array('allow_add'                => 'Add records',
													'allow_edit'             => 'Edit records',
													'allow_delete'           => 'Delete records',
													'allow_download'         => 'File download',
													'allow_upload'           => 'File upload',
													'allow_details_view'     => 'Details view',
													'allow_print_view'       => 'Print view',
													'allow_browse_view'      => 'Browse view',
													'allow_sql_search'       => 'SQL search',
													'allow_user_groups'      => 'User groups',
													'allow_user_queries'     => 'User queries',
													'allow_rss_feeds'        => 'RSS feeds',
													'allow_import'           => 'Import',
													'allow_export'           => 'Export',
													'allow_cite'             => 'Cite',
													'allow_batch_import'     => 'Batch import',
													'allow_batch_export'     => 'Batch export',
													'allow_modify_options'   => 'Modify options');
//													'allow_edit_call_number' => 'Edit call number');

				if (empty($enabledUserActionsArray))
					$enabledUserActionsArray[] = "(none)";
				else
					foreach($enabledUserActionsArray as $permissionKey => $permissionName)
						$enabledUserActionsArray[$permissionKey] = searchReplaceText($searchReplaceActionsArray, $permissionName, false); // function 'searchReplaceText()' is defined in 'include.inc.php'

				if (empty($disabledUserActionsArray))
					$disabledUserActionsArray[] = "(none)";
				else
					foreach($disabledUserActionsArray as $permissionKey => $permissionName)
						$disabledUserActionsArray[$permissionKey] = searchReplaceText($searchReplaceActionsArray, $permissionName, false); // function 'searchReplaceText()' is defined in 'include.inc.php'

				echo "\n\t\t<tr>\n\t\t\t<td colspan=\"2\"></td>\n\t\t</tr>";

				echo "\n\t\t<tr>\n\t\t\t<th align=\"left\" class=\"smaller\" colspan=\"2\">User Permissions:</th>\n\t\t</tr>";

				echo "\n\t\t<tr valign=\"top\">"
					. "\n\t\t\t<td>Enabled features:</td>"
					. "\n\t\t\t<td>\n\t\t\t\t<ul type=\"none\" class=\"smallup\">\n\t\t\t\t\t<li>" . implode("</li>\n\t\t\t\t\t<li>", $enabledUserActionsArray) . "</li>\n\t\t\t\t</ul>\n\t\t\t</td>"
					. "\n\t\t</tr>";

				echo "\n\t\t<tr valign=\"top\">"
					. "\n\t\t\t<td>Disabled features:</td>"
					. "\n\t\t\t<td>\n\t\t\t\t<ul type=\"none\" class=\"smallup\">\n\t\t\t\t\t<li>" . implode("</li>\n\t\t\t\t\t<li>", $disabledUserActionsArray) . "</li>\n\t\t\t\t</ul>\n\t\t\t</td>"
					. "\n\t\t</tr>";
			}

			if ($userAction != "Delete")
				echo "\n\t\t<tr>"
					. "\n\t\t\t<td colspan=\"2\">"
					. "\n\t\t\t\t<form action=\"user_options.php\" method=\"POST\">"
					. "\n\t\t\t\t\t<input type=\"hidden\" name=\"userID\" value=\"" . $userID . "\">"
					. "\n\t\t\t\t\t<input type=\"submit\" value=\"" . $userAction . " Options\">"
					. "\n\t\t\t\t</form>"
					. "\n\t\t\t</td>"
					. "\n\t\t</tr>";

			// Close right sub-table:
			echo "\n\t\t</table>";
	
			// Close right table cell of main table:
			echo "\n\t</td>";

			echo "\n</tr>";

		// Close main table:
		echo "\n</table>";
	}

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'showPageFooter()' and 'displayHTMLfoot()' functions (which are defined in 'footer.inc.php')
	showPageFooter($HeaderString, "");

	displayHTMLfoot();

	// --------------------------------------------------------------------
?>
