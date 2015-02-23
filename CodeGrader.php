<?php

include_once("Grader.php");
include_once("AnalysisParser.php");
include_once("ResultGenerator.php");

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

$parser = new \legovaer\AnalysisParser();
$analysis = $parser->analyze();

$grader = new \legovaer\Grader($analysis);

$metrics = array(
  CSHIGH,
  CSNORMAL,
  TESTING,
  CYCLOCOMPLEX,
  DUPCODE,
  PMDHIGH,
  PMDLOW,
  CLOC,
);

$analysis = $grader->analyze($metrics);
$standards = $grader->getStandards($metrics);
$title = "My Awesome Project";

$result = new \legovaer\ResultGenerator();
$result->setAnalysis($analysis, $standards, $title);
$result->generate();
?>