<?php

include_once("Grader.php");
include_once("AnalysisParser.php");
include_once("ResultGenerator.php");
include_once("CLI.php");

define("LOC", "loc");
define("NLOC", "nloc");
define("CSHIGH", "cshigh");
define("CSNORMAL", "csnormal");
define("TESTING", "testing");
define("CYCLOCOMPLEX", "cyclocomplex");
define("DUPCODE", "dupcode");
define("PMDHIGH", "pmdhigh");
define("PMDLOW", "pmdlow");
define("CLOC", "cloc");

$cli = new \legovaer\CLI();

?>