<?php
/**
 * Created by PhpStorm.
 * User: legovaer
 * Date: 20/02/15
 * Time: 08:15
 */

namespace legovaer;


class AnalysisParser {
  private $loc_file;
  private $checkstyle_file;
  private $pmd_file;
  private $dry_file;
  private $analysis;

  public function __construct() {
    $this->analysis = new \stdClass();
    $this->checkDefaultFiles();
  }

  public function setLocFile($loc_file) {
    $this->loc_file = $loc_file;
  }

  public function setCheckStyleFile($checkstyle_file) {
    $this->checkstyle_file = $checkstyle_file;
  }

  public function setDryFile($dry_file) {
    $this->dry_file = $dry_file;
  }

  public function setPmdFile($pmd_file) {
    $this->pmd_file = $pmd_file;
  }

  public function analyze() {
    $this->parsePhpLoc();
    $this->parseCheckStyleXml();
    $this->parseDryXml();
    $this->parsePmdXml();
    return $this->analysis;
  }

  private function checkDefaultFiles() {
    if (file_exists('src/phploc.csv')) {
      $this->loc_file = 'src/phploc.csv';
    }

    if (file_exists('src/checkstyle-warnings.xml')) {
      $this->checkstyle_file = 'src/checkstyle-warnings.xml';
    }

    if (file_exists('src/dry-warnings.xml')) {
      $this->dry_file = 'src/dry-warnings.xml';
    }

    if (file_exists('src/pmd-warnings.xml')) {
      $this->pmd_file = 'src/pmd-warnings.xml';
    }
  }

  private function checkPhpLocFile() {
    if (!isset($this->loc_file)) {
      throw new \Exception("The csv file with the phploc results has not been set.");
    }

    if(!file_exists($this->loc_file)) {
      throw new \Exception("The csv file ($this->loc_file) with the phploc results cannot be found.");
    }
  }

  private function checkCheckStyleFile() {
    if (!isset($this->checkstyle_file)) {
      throw new \Exception("The xml file with the checkstyle results has not been set.");
    }

    if (!file_exists($this->checkstyle_file)) {
      throw new \Exception("The xml file ($this->checkstyle_file) with the checkstyle results cannot be found.");
    }
  }

  private function checkPmdFile() {
    if (!isset($this->pmd_file)) {
      throw new \Exception("The xml file with the PMD results has not been set.");
    }

    if (!file_exists($this->pmd_file)) {
      throw new \Exception("The xml file ($this->pmd_file) with the PMD results cannot be found.");
    }
  }

  private function checkDryFile() {
    if (!isset($this->dry_file)) {
      throw new \Exception("The xml file with the DRY results has not been set.");
    }

    if (!file_exists($this->dry_file)) {
      throw new \Exception("The xml file ($this->dry_file) with the DRY results cannot be found.");
    }
  }

  private function parsePhpLoc() {
    $this->checkPhpLocFile();

    $csv = array_map("str_getcsv", file($this->loc_file, FILE_SKIP_EMPTY_LINES));
    $keys = array_shift($csv);
    foreach ($csv as $i => $row) {
      $csv[$i] = array_combine($keys, $row);
    }
    $csv = $csv[0];

    $this->analysis->loc = (int) $csv['Lines of Code (LOC)'];
    $this->analysis->nloc = (int) $csv['Non-Comment Lines of Code (NCLOC)'];
    $this->analysis->cloc = (int) $csv['Comment Lines of Code (CLOC)'];
    $this->analysis->cyclocomplex = (float) $csv['Cyclomatic Complexity / Lines of Code'];
    $this->analysis->testing = (int) $csv['Test Methods'];

  }

  private function parseCheckStyleXml() {
    $this->checkCheckStyleFile();

    $xml = simplexml_load_file($this->checkstyle_file);

    $normal = 0;
    $high = 0;

    foreach ($xml->children() as $child) {
      if ((string) $child->priority == "HIGH") {
        $high++;
      }
      elseif ((string) $child->priority == "NORMAL") {
        $normal++;
      }
    }

    $this->analysis->csnormal = (int) $normal;
    $this->analysis->cshigh = (int) $high;
  }

  private function parsePmdXml() {
    $this->checkPmdFile();

    $xml = simplexml_load_file($this->pmd_file);

    $normal = 0;
    $high = 0;

    foreach ($xml->children() as $child) {
      if ((string) $child->priority == "HIGH") {
        $high++;
      }
      elseif ((string) $child->priority == "NORMAL") {
        $normal++;
      }
    }

    $this->analysis->pmdlow = (int) $normal;
    $this->analysis->pmdhigh = (int) $high;
  }

  private function parseDryXml() {
    $this->checkDryFile();

    $xml = simplexml_load_file($this->dry_file);

    $high = 0;

    foreach ($xml->children() as $child) {
      if ((string) $child->priority == "HIGH") {
        $high++;
      }
    }

    $this->analysis->dupcode = (int) $high;
  }

}
