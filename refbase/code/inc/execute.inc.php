<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author.
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY.  Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/execute.inc.php
	// Author:     Richard Karnesky <mailto:karnesky@northwestern.edu> and
	//             Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    16-Dec-05, 18:00
	// Modified:   27-Feb-06, 16:15

	// This file contains functions that deal with execution of shell commands and provides
	// fixes for 'exec()' on certain win32 systems (based on rivera at spamjoy dot unr dot edu's
	// 'wind_exec()' function <http://php.net/function.exec>).

	// Note: Since the 'exec()' function is used, some things may not work if
	//'safe_mode' is set to 'On' in your 'php.ini' file. If you need or want to
	// keep 'safe_mode=ON' then you'll need to put the programs within the
	// directory that's specified in 'safe_mode_exec_dir'.

	// --------------------------------------------------------------------

	// Import records using the bibutils program given in '$program'
	function importBibutils($sourceText, $program)
	{
		// Get the absolute path for the bibutils package:
		// (function 'getExternalUtilityPath()' is defined in 'include.inc.php')
		$bibutilsPath = getExternalUtilityPath("bibutils");

		// Get the path to the system's temporary directory:
		$tempDirPath = getTempDirPath();

		// Write the source data to a temporary file:
		$tempFile = writeToTempFile($tempDirPath, $sourceText);

		// Pass this temp file to the bibutils utility for conversion:
		$outputFile = convertBibutils($bibutilsPath, $tempDirPath, $tempFile, $program);
		unlink($tempFile);

		// Read the resulting output file and return the converted data:
		$resultString = readFromFile($outputFile);
		unlink($outputFile);

		return $resultString;
	}

	// --------------------------------------------------------------------

	// Export records using the bibutils program given in '$program'
	function exportBibutils($result, $program)
	{
		// Get the absolute path for the bibutils package:
		// (function 'getExternalUtilityPath()' is defined in 'include.inc.php')
		$bibutilsPath = getExternalUtilityPath("bibutils");

		// Generate and serve a MODS XML file of ALL records:
		// (function 'modsCollection()' is defined in 'modsxml.inc.php')
		$recordCollection = modsCollection($result);

		// Get the path to the system's temporary directory:
		$tempDirPath = getTempDirPath();

		// Write the MODS XML data to a temporary file:
		$tempFile = writeToTempFile($tempDirPath, $recordCollection);

		// Pass this temp file to the bibutils utility for conversion:
		$outputFile = convertBibutils($bibutilsPath, $tempDirPath, $tempFile, $program);
		unlink($tempFile);

		// Read the resulting output file and return the converted data:
		$resultString = readFromFile($outputFile);
		unlink($outputFile);

		return $resultString;
	}

	// --------------------------------------------------------------------

	// Convert file contents using the bibutils program given in '$program'
	function convertBibutils($bibutilsPath, $tempDirPath, $tempFile, $program)
	{
		$outputFile = tempnam($tempDirPath, "refbase-");
		$cmd = $bibutilsPath . $program . " " . $tempFile . " > " . $outputFile;
		execute($cmd);

		return $outputFile;
	}

	// --------------------------------------------------------------------

	// Execute shell command
	function execute($cmd)
	{
		if (getenv("OS") == "Windows_NT")
			executeWin32($cmd);
		else
			exec($cmd);
	}

	// --------------------------------------------------------------------

	// Execute shell command on win32 systems
	function executeWin32($cmd)
	{
		$cmdline = "cmd /C ". $cmd;

		// Make a new instance of the COM object
		$WshShell = new COM("WScript.Shell");

		// Make the command window but dont show it
		$oExec = $WshShell->Run($cmdline, 0, true);
	}

	// --------------------------------------------------------------------

	// Get the path to the system's temporary directory
	function getTempDirPath()
	{
		// Get the path of the current directory that's used to save session data
		$tempDirPath = session_save_path();

		return $tempDirPath;
	}

	// --------------------------------------------------------------------

	// Write data to a temporary file
	function writeToTempFile($tempDirPath, $sourceText)
	{
		$tempFile = tempnam($tempDirPath, "refbase-");
		$tempFileHandle = fopen($tempFile, "w"); // open temp file with write permission
		fwrite($tempFileHandle, $sourceText); // save data to temp file
		fclose($tempFileHandle); // close temp file

		return $tempFile;
	}

	// --------------------------------------------------------------------

	// Get file contents
	function readFromFile($file)
	{
		$fileContents = file_get_contents($file);

		return $fileContents;
	}

	// --------------------------------------------------------------------
?>
