<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./index.php
	// Created:    29-Jul-02, 16:45
	// Modified:   02-Nov-04, 00:44

	// This script builds the main page.
	// It provides login and quick search forms
	// as well as links to various search forms.

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
	include 'includes/locales.inc.php'; // include the locales

	// --------------------------------------------------------------------

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session(true);

	// --------------------------------------------------------------------

	// If there's no stored message available:
	if (!isset($_SESSION['HeaderString']))
		$HeaderString = $loc["Default Welcome Message"]; // Provide the default welcome message
	else
	{
		$HeaderString = $_SESSION['HeaderString']; // extract 'HeaderString' session variable (only necessary if register globals is OFF!)

		// Note: though we clear the session variable, the current message is still available to this script via '$HeaderString':
		deleteSessionVariable("HeaderString"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
	}

	// Extract the view type requested by the user (either 'Print', 'Web' or ''):
	// ('' will produce the default 'Web' output style)
	if (isset($_REQUEST['viewType']))
		$viewType = $_REQUEST['viewType'];
	else
		$viewType = "";

	// CONSTRUCT SQL QUERY:
	$query = "SELECT COUNT(serial) FROM refs"; // query the total number of records

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE
	connectToMySQLDatabase(""); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

	// (3a) RUN the query on the database through the connection:
	$result = queryMySQLDatabase($query, ""); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

	// (3b) EXTRACT results:
	$row = mysql_fetch_row($result); //fetch the current row into the array $row (it'll be always *one* row, but anyhow)
	$recordCount = $row[0]; // extract the contents of the first (and only) row

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

	// (4) DISPLAY header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
	displayHTMLhead(htmlentities($officialDatabaseName) . " -- Home", "index,follow", "Search the " . htmlentities($officialDatabaseName), "", false, "", $viewType);
	showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks, "");

	// (5) CLOSE the database connection:
	disconnectFromMySQLDatabase(""); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------
?>

<table align="center" border="0" cellpadding="2" cellspacing="5" width="90%" summary="This table explains features, goals and usage of the <? echo htmlentities($officialDatabaseName); ?>">
	<tr>
		<td colspan="2"><h3><?php echo $loc["Goals"]; ?> &amp; <?php echo $loc["Features"]; ?></h3></td>
		<td width="180" valign="bottom"><?php
if (!isset($_SESSION['loginEmail']))
	{
?><div class="header"><b><?php echo $loc["Login"]; ?>:</b></div><?php
	}
else
	{
?><div class="header"><b><?php echo $loc["ShowMyRefs"]; ?>:</b></div><?php
	}
?></td>
	</tr>
	<tr>
		<td width="15">&nbsp;</td>
		<td><? echo $loc["ThisDatabaseAttempts"] . htmlentities($scientificFieldDescriptor); ?>.
			<br>
			<br>
			<?php echo $loc["ThisDatabase"] ." ". $loc["provides"]; ?>
			<ul type="circle">
				<li>a comprehensive dataset on <? echo htmlentities($scientificFieldDescriptor)." ".$loc["Literature"]; 
	// report the total number of records:
	echo ", ". $loc["currently featuring"] . $recordCount . $loc["Records"];
	?>
	</li>
	<?php echo $loc["ListOfFeatures"]; ?>

				
				<li><?php

		// -------------------------------------------------------
		if (isset($_SESSION['user_permissions']) AND ereg("(allow_import|allow_batch_import)", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains either 'allow_import' or 'allow_batch_import'...
		{
		// ... include a link to 'import_csa.php':
			echo "<a href=\"import_csa.php\">". $loc["Import"] ."</a>";
		}
		else
		{
			echo $loc["Import"];
		}

		// -------------------------------------------------------

			echo $loc["CSAImportLinkTitle"] . "</li>"; ?>
			</ul>
		</td>
		<td width="163" valign="top">
<?php
if (!isset($_SESSION['loginEmail']))
	{
?>
			<form action="user_login.php" method="POST">
				<?php echo $loc["EmailAddress"]; ?>:
				<br>
				<input type="text" name="loginEmail" size="12">
				<br>
				<?php echo $loc["Password"]; ?>:
				<br>
				<input type="password" name="loginPassword" size="12">
				<br>
				<input type="submit" value="Login">
			</form><?php
	}
else
	{
?>
			<form action="search.php" method="POST">
				<input type="hidden" name="formType" value="myRefsSearch">
				<input type="hidden" name="showQuery" value="0">
				<input type="hidden" name="showLinks" value="1">
				<input type="radio" name="myRefsRadio" value="1" checked>&nbsp;<?php echo $loc["All"]; ?>
				<br>
				<input type="radio" name="myRefsRadio" value="0">&nbsp;<?php echo $loc["Only"]; ?>:
				<br>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="findMarked" value="1">
				<select name="markedSelector">
					<option><?php echo $loc["marked"]; ?></option>
					<option><?php echo $loc["not"]." ". $loc["marked"]; ?></option>
				</select>
				<br>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="findSelected" value="1">
				<select name="selectedSelector">
					<option><?php echo $loc["selected"]; ?></option>
					<option><?php echo $loc["not"]." ". $loc["selected"]; ?></option>
				</select>
				<br>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="findCopy" value="1">&nbsp;<?php echo $loc["copy"]; ?>:
				<select name="copySelector">
					<option><?php echo $loc["true"]; ?></option>
					<option><?php echo $loc["fetch"]; ?></option>
					<option><?php echo $loc["ordered"]; ?></option>
					<option><?php echo $loc["false"]; ?></option>
				</select>
				<br>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="findUserKeys" value="1">&nbsp;<?php echo $loc["key"]; ?>:&nbsp;
				<input type="text" name="userKeysName" size="7">
				<br>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="findUserNotes" value="1">&nbsp;<?php echo $loc["note"]; ?>:&nbsp;
				<input type="text" name="userNotesName" size="7">
				<br>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="findUserFile" value="1">&nbsp;<?php echo $loc["file"]; ?>:&nbsp;&nbsp;&nbsp;
				<input type="text" name="userFileName" size="7">
				<br>
				<input type="submit" value="<?php echo $loc["Search"]; ?>">
			</form><?php
	}
?>

		</td>
	</tr>
	<tr>
		<td colspan="2"><h3><?php echo $loc["Search"]; ?></h3></td>
		<td width="163" valign="bottom"><div class="header"><b><?php echo $loc["QuickSearch"]; ?>:</b></div></td>
	</tr>
	<tr>
		<td width="15">&nbsp;</td>
		<td><?php echo $loc["SearchDB"]; ?>:
			<ul type="circle">
				<li><a href="simple_search.php"><?php echo $loc["simple"]; ?> <?php echo $loc["Search"]; ?></a>&nbsp;&nbsp;&nbsp;&#8211;&nbsp;&nbsp;&nbsp;<?php echo $loc["SearchMain"]; ?></li>
				<li><a href="advanced_search.php"><?php echo $loc["Advanced"]; ?> <?php echo $loc["Search"]; ?></a>&nbsp;&nbsp;&nbsp;&#8211;&nbsp;&nbsp;&nbsp;<?php echo $loc["SearchAll"]; ?></li><?php

		// -------------------------------------------------------
		if (isset($_SESSION['user_permissions']) AND ereg("allow_sql_search", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_sql_search'...
		{
		// ... include a link to 'sql_search.php':
?>

				<li><a href="sql_search.php">SQL <?php echo $loc["Search"]; ?></a>&nbsp;&nbsp;&nbsp;&#8211;&nbsp;&nbsp;&nbsp;<?php echo $loc["SearchSQL"]; ?></li><?php
		}

		// -------------------------------------------------------
?>

				<li><a href="library_search.php"><?php echo $loc["Library"]; ?> <?php echo $loc["Search"]; ?></a>&nbsp;&nbsp;&nbsp;&#8211;&nbsp;&nbsp;&nbsp;<?php echo $loc["SearchExt"]; ?> <? echo htmlentities($hostInstitutionName); ?></li>
			</ul>
		</td>
		<td width="163" valign="top">
			<form action="search.php" method="POST">
				<input type="hidden" name="formType" value="quickSearch">
				<input type="hidden" name="showQuery" value="0">
				<input type="hidden" name="showLinks" value="1">
				<select name="quickSearchSelector">
					<option selected><?php echo $loc["author"]; ?></option>
					<option><?php echo $loc["title"]; ?></option>
					<option><?php echo $loc["year"]; ?></option>
					<option><?php echo $loc["keywords"]; ?></option>
					<option><?php echo $loc["abstract"]; ?></option>
				</select>
				<br>
				<input type="text" name="quickSearchName" size="12">
				<br>
				<input type="submit" value="<?php echo $loc["Search"]; ?>">
			</form>
		</td>
	</tr>
	<tr>
		<td width="15">&nbsp;</td>
		<td><?php echo $loc["or"]; ?>:</td>
		<td width="163" valign="top">
<?php
if (isset($_SESSION['loginEmail']) AND (isset($_SESSION['user_permissions']) AND ereg("allow_user_groups", $_SESSION['user_permissions'])))
	{
?>
			<div class="header"><b><?php echo $loc["ShowMyGroup"]; ?>:</b></div><?php
	}
else
	{
?>
			&nbsp;<?php
	}
?>

		</td>
	</tr>
	<tr>
		<td width="15">&nbsp;</td>
		<td>
<?php
	// Get the current year & date in order to include them into query URLs:
	$CurrentYear = date('Y');
	$CurrentDate = date('Y-m-d');
	// We'll also need yesterday's date for inclusion into query URLs:
	$TimeStampYesterday = mktime(0, 0, 0, date('m'), (date('d') - 1), date('Y'));
	$DateYesterday = date('Y-m-d', $TimeStampYesterday);
	// Plus, we'll calculate the date that's a week ago (again, for inclusion into query URLs):
	$TimeStampLastWeek = mktime(0, 0, 0, date('m'), (date('d') - 7), date('Y'));
	$DateLastWeek = date('Y-m-d', $TimeStampLastWeek);
?>
			<ul type="circle" class="moveup">
				<li><?php echo $loc["view all"]; ?>:
					<ul type="circle">
						<li><?php echo $loc["added"]; ?>: <a href="show.php?date=<? echo $CurrentDate; ?>"><?php echo $loc["today"]; ?></a> | <a href="show.php?date=<? echo $DateYesterday; ?>"><?php echo $loc["yesterday"]; ?></a> | <a href="show.php?date=<? echo $DateLastWeek; ?>&amp;range=after"><?php echo $loc["last 7 days"]; ?></a></li>
						<li><?php echo $loc["edited"]; ?>: <a href="show.php?date=<? echo $CurrentDate; ?>&amp;when=edited"><?php echo $loc["today"]; ?></a> | <a href="show.php?date=<? echo $DateYesterday; ?>&amp;when=edited"><?php echo $loc["yesterday"]; ?></a> | <a href="show.php?date=<? echo $DateLastWeek; ?>&amp;when=edited&amp;range=after"><?php echo $loc["last 7 days"]; ?></a></li>
						<li><?php echo $loc["published in"]; ?>: <a href="show.php?year=<? echo $CurrentYear; ?>"><? echo $CurrentYear; ?></a> | <a href="show.php?year=<? echo ($CurrentYear - 1); ?>"><? echo ($CurrentYear - 1); ?></a> | <a href="show.php?year=<? echo ($CurrentYear - 2); ?>"><? echo ($CurrentYear - 2); ?></a> | <a href="show.php?year=<? echo ($CurrentYear - 3); ?>"><? echo ($CurrentYear - 3); ?></a></li>
					</ul>
				</li>
			</ul>
		</td>
		<td width="163" valign="top">
<?php
if (isset($_SESSION['loginEmail']) AND (isset($_SESSION['user_permissions']) AND ereg("allow_user_groups", $_SESSION['user_permissions']))) // if a user is logged in AND the 'user_permissions' session variable contains 'allow_user_groups', show the 'Show My Groups' form:
	{
		if (!isset($_SESSION['userGroups']))
			$groupSearchDisabled = " disabled"; // disable the 'Show My Groups' form if the session variable holding the user's groups isnt't available
		else
			$groupSearchDisabled = "";
?>
			<form action="search.php" method="POST">
				<input type="hidden" name="formType" value="groupSearch">
				<input type="hidden" name="showQuery" value="0">
				<input type="hidden" name="showLinks" value="1">
				<select name="groupSearchSelector"<? echo $groupSearchDisabled; ?>><?php

				if (isset($_SESSION['userGroups']))
				{
					$optionTags = buildSelectMenuOptions($_SESSION['userGroups'], " *; *", "\t\t\t\t\t", false); // build properly formatted <option> tag elements from the items listed in the 'userGroups' session variable
					echo $optionTags;
				}
				else
				{
?>

					<option>(<?php echo $loc["NoGroupsAvl"]; ?>)</option><?php
				}
?>

				</select>
				<br>
				<input type="submit" value="Show"<? echo $groupSearchDisabled; ?>>
			</form><?php
	}
else
	{
?>
			&nbsp;<?php
	}
?>

		</td>
	</tr>
	<tr>
		<td width="15">&nbsp;</td>
		<td>
<?php
if (isset($_SESSION['user_permissions']) AND ereg("(allow_details_view|allow_cite)", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable either contains 'allow_details_view' or 'allow_cite'...
	{
?>
			<?php echo $loc["Tools serial nums"]; ?>:<?php
	}
else
	{
?>
			&nbsp;<?php
	}
?>

		</td>
		<td width="163" valign="top">
<?php
if (isset($_SESSION['loginEmail']) AND (isset($_SESSION['user_permissions']) AND ereg("allow_user_queries", $_SESSION['user_permissions'])))
	{
?>
			<div class="header"><b>Recall My Query:</b></div><?php
	}
else
	{
?>
			&nbsp;<?php
	}
?>

		</td>
	</tr>
	<tr>
		<td width="15">&nbsp;</td>
		<td>
			<ul type="circle" class="moveup"><?php
if (isset($_SESSION['user_permissions']) AND ereg("allow_details_view", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_details_view'...
	{
?>

				<li><a href="show.php"><?php echo $loc["SearchSerial"]; ?></a></li><?php
	}

if (isset($_SESSION['user_permissions']) AND ereg("allow_cite", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_cite'...
	{
?>

				<li><a href="extract.php"><?php echo $loc["ExtractCitations"]; ?></a></li><?php
	}
?>

			</ul>
		</td>
		<td width="163" valign="top">
<?php
if (isset($_SESSION['loginEmail']) AND (isset($_SESSION['user_permissions']) AND ereg("allow_user_queries", $_SESSION['user_permissions']))) // if a user is logged in AND the 'user_permissions' session variable contains 'allow_user_queries', show the 'Recall My Query' form:
	{
		if (!isset($_SESSION['userQueries']))
			$querySearchDisabled = " disabled"; // disable the 'Recall My Query' form if the session variable holding the user's queries isn't available
		else
			$querySearchDisabled = "";
?>
			<form action="queries.php" method="POST">
				<input type="hidden" name="formType" value="querySearch">
				<input type="hidden" name="showQuery" value="0">
				<input type="hidden" name="showLinks" value="1">
				<select name="querySearchSelector"<? echo $querySearchDisabled; ?>><?php

				if (isset($_SESSION['userQueries']))
				{
					$optionTags = buildSelectMenuOptions($_SESSION['userQueries'], " *; *", "\t\t\t\t\t", false); // build properly formatted <option> tag elements from the items listed in the 'userQueries' session variable
					echo $optionTags;
				}
				else
				{
?>

					<option>(<?php echo $loc["NoQueriesAvl"]; ?>)</option><?php
				}
?>

				</select>
				<br>
				<input type="submit" name="submit" value="Go"<? echo $querySearchDisabled; ?>>&nbsp;<input type="submit" name="submit" value="Edit"<? echo $querySearchDisabled; ?>>
			</form><?php
	}
else
	{
?>
			&nbsp;<?php
	}
?>

		</td>
	</tr>
	<tr>
		<td colspan="3"><h3><?php echo $loc["about"]; ?></h3></td>
	</tr>
	<tr>
		<td width="15">&nbsp;</td>
    <td><?php echo $loc["ThisDatabaseIsMaintained"]; ?> <a href="<? echo $hostInstitutionURL; ?>"><? echo htmlentities($hostInstitutionName); ?></a> (<? echo htmlentities($hostInstitutionAbbrevName); ?>). <?php echo $loc["You are welcome to send"]; ?> <a href="mailto:<? echo $feedbackEmail; ?>"><?php echo $loc["feedback address"]; ?></a>. <?php echo $loc["refbaseDesc"]; ?></td>
		<td width="163" valign="top"><a href="http://www.refbase.net/"><img src="img/refbase_credit.gif" alt="powered by refbase" width="80" height="44" hspace="0" border="0"></a></td>
	</tr>
</table><?php

	// --------------------------------------------------------------------

	//	DISPLAY THE HTML FOOTER:
	// call the 'displayfooter()' function from 'footer.inc.php')
	displayfooter("");

	// --------------------------------------------------------------------
?>

</body>
</html> 
