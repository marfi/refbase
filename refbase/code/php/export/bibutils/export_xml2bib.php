<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./export/bibutils/export_xml2bib.php
	// Created:    28-Sep-04, 22:14
	// Modified:   09-May-06, 16:42

	// This is an export format file (which must reside within the 'export/' sub-directory of your refbase root directory). It contains a version of the
	// 'exportRecords()' function that outputs records according to the export format used by 'BibTeX', the bibliographic companion to the LaTeX macro package.
	// This function is basically a wrapper for the bibutils 'xml2bib' command line tool (http://www.scripps.edu/~cdputnam/software/bibutils/bibutils.html).

	// --------------------------------------------------------------------

	// --- BEGIN EXPORT FORMAT ---

	// Export found records in 'BibTeX' format:

	// Requires the following packages (available under the GPL):
	//    - bibutils <http://www.scripps.edu/~cdputnam/software/bibutils/bibutils.html>
	//    - ActiveLink PHP XML Package <http://www.active-link.com/software/>

	include 'includes/execute.inc.php';
	include 'includes/export.inc.php';
	function exportRecords($result, $rowOffset, $showRows, $exportStylesheet, $displayType)
	{
		// function 'exportBibutils()' is defined in 'execute.inc.php'
		$bibtexSourceText = exportBibutils($result,"xml2bib");

		// function 'standardizeBibtexOutput()' is defined in 'export.inc.php'
		return standardizeBibtexOutput($bibtexSourceText);
	}

	// --- END EXPORT FORMAT ---

	// --------------------------------------------------------------------
?>
