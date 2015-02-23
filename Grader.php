<?php
/**
 * User: legovaer
 * Date: 20/02/15
 * Time: 08:08
 */

namespace legovaer;


class Grader {
  private $cloc;
  private $loc;
  private $nloc;
  private $cshigh;
  private $csnormal;
  private $testing;
  private $cyclocomplex;
  private $dupcode;
  private $pmdhigh;
  private $pmdlow;
  private $grades = array(
    CSHIGH => array(
      "a+" => 0,
      "a" => 0.03,
      "b" => 0.06,
      "c" => 0.09,
      "d" => 0.12,
      "e" => 0.15,
    ),
    CSNORMAL => array(
      "a+" => 0,
      "a" => 0.06,
      "b" => 0.12,
      "c" => 0.18,
      "d" => 0.24,
      "e" => 0.3,
    ),
    TESTING => array(
      "a+" => 0.1,
      "a" => 0.08,
      "b" => 0.05,
      "c" => 0.03,
      "d" => 0.01,
      "e" => 0,
    ),
    CYCLOCOMPLEX => array(
      "a+" => 0,
      "a" => 0.2,
      "b" => 0.4,
      "c" => 0.6,
      "d" => 0.8,
      "e" => 1,
    ),
    DUPCODE => array(
      "a+" => 0,
      "a" => 1,
      "b" => 2,
      "c" => 4,
      "d" => 6,
      "e" => 8,
    ),
    PMDHIGH => array(
      "a+" => 0,
      "a" => 0.0005,
      "b" => 0.002,
      "c" => 0.003,
      "d" => 0.005,
      "e" => 0.007,
    ),
    PMDLOW => array(
      "a+" => 0,
      "a" => 0.002,
      "b" => 0.005,
      "c" => 0.007,
      "d" => 0.01,
      "e" => 0.12,
    ),
    CLOC => array(
      "a+" => 0.4,
      "a" => 0.3,
      "b" => 0.2,
      "c" => 0.1,
      "d" => 0.09,
      "e" => 0.08,
    ),
  );

  public function __construct($results) {
    $this->extractValuesFromAnalysis($results);
  }

  private function extractValuesFromAnalysis($results) {
    $metrics = array(
      LOC,
      NLOC,
      CSHIGH,
      CSNORMAL,
      TESTING,
      CYCLOCOMPLEX,
      DUPCODE,
      PMDHIGH,
      PMDLOW,
      CLOC,
    );
    foreach ($metrics as $metric) {
      isset($results->$metric) ? $this->$metric = $results->$metric : $this->$metric = FALSE;
    }
  }

  private function _checkGrade($score, $subject) {
    $grade = "e";

    if ($score < $this->grades[$subject]['a']) {
      $grade = "a+";
    }
    elseif ($score < $this->grades[$subject]['b']) {
      $grade = "a";
    }
    elseif ($score < $this->grades[$subject]['c']) {
      $grade = "b";
    }
    elseif ($score < $this->grades[$subject]['d']) {
      $grade = "c";
    }
    elseif ($score < $this->grades[$subject]['e']) {
      $grade = "d";
    }

    return $grade;
  }


  private function _checkReverseGrade($score, $subject) {
    $grade = "e";

    if ($score > $this->grades[$subject]['a+']) {
      $grade = "a+";
    }
    elseif ($score > $this->grades[$subject]['a']) {
      $grade = "a";
    }
    elseif ($score > $this->grades[$subject]['b']) {
      $grade = "b";
    }
    elseif ($score > $this->grades[$subject]['c']) {
      $grade = "c";
    }
    elseif ($score > $this->grades[$subject]['d']) {
      $grade = "d";
    }

    return $grade;
  }

  public function analyze($metric) {
    if (is_array($metric)) {
      $grades = array();
      foreach ($metric as $single_metric) {
        $grades[] = $this->analyzeMetric($single_metric);
      }
      $return = array(
        'grades' => $grades,
        'overall' => $this->calculateOverallGrade($grades),
      );
      return $return;
    }
    else {
      return $this->analyzeMetric($metric);
    }
  }

  public function getStandards($metrics) {
    $standards = array();

    foreach ($metrics as $metric) {
      $standards[$metric] = $this->grades[$metric];
    }

    return $standards;
  }

  private function calculateOverallGrade($analysis) {
    $grades = array(
      'e' => 1,
      'd' => 2,
      'c' => 3,
      'b' => 4,
      'a' => 5,
      'a+' => 6,
    );

    $total = 0;
    foreach($analysis as $metric) {
      $total += $grades[$metric['grade']];
    }

    $average = round($total / count($analysis));

    return array_search($average, $grades);
  }

  private function getFullMetricName($metric) {
    $names = array(
      LOC => "The total amount of lines of code (LOC) inside the codebase. This amount includes comments.",
      NLOC => "The total amount of non-comment lines of code (NCLOC) inside the codebase.",
      CSHIGH => "High prioritized checkstyle warnings compared with the total amount of lines of code.",
      CSNORMAL => "Normal prioritized checkstyle warnings compared with the total amount of lines of code.",
      TESTING => "Amount of test methods that are available compared with the total amount of lines of code. Note: this is not the unit test report.",
      CYCLOCOMPLEX => "The cyclomatic complexity compared with the total amount of lines of code. Cyclomatic complexity indicates how difficult it is in order to understand the code.",
      DUPCODE => "Amount of blocks of duplicated code inside the codebase.",
      PMDHIGH => "The amount of high prioritized PMD warnings compared with the total amount of lines of code.",
      PMDLOW => "The amount of normal prioritized PMD Warnings compared with the total amount of lines of code.",
      CLOC => "The total amount of comments lines of code (CLOC) compared with the total amount of lines of code.",
    );
    return array_key_exists($metric, $names) ? $names[$metric] : $metric;
  }

  private function analyzeMetric($metric) {
    $score = NULL;
    $reverse = FALSE;
    switch ($metric) {
      case 'cshigh':
        $score = $this->cshigh / $this->nloc;
        break;

      case 'csnormal':
        $score = $this->csnormal / $this->nloc;
        break;

      case 'testing':
        $score = $this->testing / $this->nloc;
        $reverse = TRUE;
        break;

      case 'cyclocomplex':
        $score = $this->cyclocomplex;
        break;

      case 'dupcode':
        $score = $this->dupcode;
        break;

      case 'pmdhigh':
        $score = $this->pmdhigh / $this->nloc;
        break;

      case 'pmdlow':
        $score = $this->pmdlow / $this->nloc;
        break;

      case 'cloc':
        $score = $this->cloc / $this->loc;
        $reverse = TRUE;
    }

    $score = round($score, 4);

    if (is_null($score)) {
      return NULL;
    }
    else {
      if ($reverse) {
        return array(
          "metric" => $metric,
          "full_metric_name" => $this->getFullMetricName($metric),
          "score" => $score,
          "grade" => $this->_checkReverseGrade($score, $metric),
        );
      }
      else {
        return array(
          "metric" => $metric,
          "full_metric_name" => $this->getFullMetricName($metric),
          "score" => $score,
          "grade" => $this->_checkGrade($score, $metric),
        );
      }
    }
  }


}