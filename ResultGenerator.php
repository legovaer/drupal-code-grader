<?php
/**
 * Created by PhpStorm.
 * User: legovaer
 * Date: 20/02/15
 * Time: 09:29
 */

namespace legovaer;


class ResultGenerator {
  public $analysis;
  public $standards;
  public $title;

  public function setAnalysis($analysis, $standards, $title) {
    $this->analysis = $analysis;
    $this->standards = $standards;
    $this->title = $title;
  }



  private function getFullMetricName($metric_name) {
    foreach ($this->analysis['grades'] as $grade) {
      if ($grade['metric'] == $metric_name) {
        return $grade['full_metric_name'];
      }
    }
  }

  private function getScoreByMetric($metric_name) {
    foreach ($this->analysis['grades'] as $grade) {
      if ($grade['metric'] == $metric_name) {
        return $grade['score'];
      }
    }
  }

  private function getStandardMinValue($metric) {
    return $this->standards[$metric]['a+'];
  }

  private function getStandardMaxValue($metric) {
    return $this->standards[$metric]['e'];
  }

  private function standardIsReverse($metric) {
    if ($this->standards[$metric]['a+'] > $this->standards[$metric]['e']) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  private function getNextAndPrevGrade($grade) {
    switch ($grade) {
      case "a+":
        $next_grade = "a";
        $prev_grade = NULL;
        break;

      case "a":
        $next_grade = "b";
        $prev_grade = "a+";
        break;

      case "b":
        $next_grade = "c";
        $prev_grade = "a";
        break;

      case "c":
        $next_grade = "d";
        $prev_grade = "b";
        break;

      case "d":
        $next_grade = "e";
        $prev_grade = "c";
        break;

      case "e":
        $prev_grade = "d";
        $next_grade = NULL;
        break;

      default:
        $prev_grade = NULL;
        $next_grade = NULL;
        break;
    }

    return array('next_grade' => $next_grade, 'previous_grade' => $prev_grade);
  }

  private function getStandardGradeScore($metric, $grade) {
    return $this->standards[$metric][$grade];
  }

  private function getStandardDifference($metric) {
    if($this->standardIsReverse($metric)) {
      return $this->standards[$metric]['a+'] - $this->standards[$metric]['a'];
    }
    else {
      return $this->standards[$metric]['e'] - $this->standards[$metric]['d'];
    }
  }

  private function generateStandardsTable($metric) {
    $rows = '';
    foreach($this->standards[$metric] as $grade => $value) {
      $next_prev = $this->getNextAndPrevGrade($grade);

      $start_value = $value;
      $difference = $this->getStandardDifference($metric);
      $end_value = $grade == "e" ? ($value + $difference) : $this->getStandardGradeScore($metric, $next_prev['next_grade']);


      $class = $grade == "a+" ? 'a-plus' : $grade;
      $rows .= '
        <tr class="' . $class . '">
          <td>' . strtoupper($grade) . '</td>
          <td>' . $start_value . '</td>
          <td>' . $end_value . '</td>
        </tr>
      ';
    }
    $return = '<table>';
    $return .= $rows;
    $return .= '</table>';
    return $return;
  }

  private function generateWidget($metric) {
    $class = $metric['grade'] == "a+" ? 'a-plus' : $metric['grade'];
    return '
    <div class="widget">
            <div class="title"><h2>' . $metric['metric'] . '</h2></div>
            <div id="gauge-' . $metric['metric'] . '" class="gauge"></div>
            <div class="more"><a href="#" class="expander collapsed">Details</a></div>
            <div class="content">
                <div class="results">
                    <h2>' . $metric['metric'] . '</h2>
                    <h3>Grade</h3>
                    <div class="grade ' . $class . '">' . strtoupper($metric['grade']) . '</div>
                    <h3>Score</h3>
                    <span class="score">' . $metric['score'] . '</span>
                    <h3>Description</h3>
                    ' . $metric['full_metric_name'] . '
                    <span>&nbsp;</span>
                    <h3>Standards</h3>
                    ' . $this->generateStandardsTable($metric['metric']) . '
                </div>
            </div>
        </div>
    ';
  }

  private function generateOverallWidget() {
    $scores = array(
      "a+" => 6,
      "a" => 5,
      "b" => 4,
      "c" => 3,
      "d" => 2,
      "e" => 1,
    );
    $class = $this->analysis['overall'] == "a+" ? 'a-plus' : $this->analysis['overall'];

    $score = $scores[$this->analysis['overall']];
    $javascript = '
            var goverall = new JustGage({
              id: "gauge-overall",
              value: ' . $score . ',
              min: 0,
              max: 6,
              showMinMax: false,
              levelColors: ["#4bb648", "#4bb648", "#fbd109", "#ff8000", "#E26326", "#e02629"],
              title: " "
            });

       ';

    $widget = '
      <div class="widget big">
            <div class="title"><h2>Overall Details</h2></div>
            <div id="gauge-overall" class="gauge"></div>
            <div class="details">
                <h3>Project Name</h3>
                ' . $this->title . '
                <h3>Date of Analysis</h3>
                ' . date("d/m/Y H:i") . '
            </div>
            <div class="details">
              <h3>Overall Grade</h3>
              <div class="grade ' . $class . '">' . strtoupper($this->analysis['overall']) . '</div>
              <h3>Conclusion</h3>
              ' . $this->getConclusion() . '
            </div>
      </div>
    ';

    return array('javascript' => $javascript, 'widget' => $widget);
  }

  private function getConclusion() {
    switch($this->analysis['overall']) {
      case 'a+':
        return 'Code is perfect. No remarks necessary.';

      case 'a':
        return 'Code is in line with the general Drupal standards. Improvements can be done but certainly not necessary.';

      case 'b':
        return 'Code needs minor improvements. It\'s not recommended to deploy this code to a production or live environment but not forbidden.';

      case 'c':
        return 'Code needs average improvements. It\'s highly recommended to perform improvements before deploying this code to a production or live environment.';

      case 'd':
        return 'Code needs major improvements. Code with this grade is not allowed to be deployed on a production or live environment.';

      case 'e':
        return 'Code is of a very poor quality. Recommended is that the project/application manager asks for an explenation of the dev team. This code should NEVER be deployed on a production/staging environment.';
    }
  }

  public function generate() {
    $widgets = '';
    $javascript = '';

    $level_colors_reverse = '["#e02629", "#e02629", "#E26326", "#ff8000", "#fbd109", "#4bb648"]';
    $level_colors = '["#4bb648", "#4bb648", "#fbd109", "#ff8000", "#E26326", "#e02629"]';


    foreach ($this->analysis['grades'] as $metric) {
      if($this->standardIsReverse($metric['metric'])) {
        $colors = $level_colors_reverse;
        $min = $this->getStandardMaxValue($metric['metric']);
        $max = $this->getStandardMinValue($metric['metric']);

      }
      else {
        $colors = $level_colors;
        $min = $this->getStandardMinValue($metric['metric']);
        $max = $this->getStandardMaxValue($metric['metric']);
      }

      $id = 'gauge-' . $metric['metric'];

      $javascript .= '
            var g'.$metric['metric'].' = new JustGage({
              id: "' . $id . '",
              label: "' . strtoupper($metric['grade']) . '",
              value: ' . $metric['score'] . ',
              min: ' . $min . ',
              max: ' . $max . ',

              levelColors: ' . $colors . ',
              title: " "
            });

       ';
      $widgets .= $this->generateWidget($metric);

    }

    $overall = $this->generateOverallWidget();


    $html = '
    <!DOCTYPE html>
    <html>
    <head>
       <title>CodeGrading Analysis</title>
       <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
       <script src="https://code.jquery.com/ui/1.11.3/jquery-ui.js"></script>
       <link rel="stylesheet" href="https://code.jquery.com/ui/1.11.3/themes/smoothness/jquery-ui.css" type="text/css" />
       <link rel="stylesheet" href="styles/codegrader.css" type="text/css" />
       <script src="js/raphael.2.1.0.min.js"></script>
       <script src="js/justgage.1.0.1.min.js"></script>
       <script src="js/masonry.pkgd.min.js"></script>
       <script type="text/javascript">
           $(document).ready(function () {';
    $html .= $javascript;
    $html .= $overall['javascript'];
    $html .= '
                $(\'#dialog\').dialog({
                    autoOpen: false,
                    title: "Details",
                    position: { my: "top" }
                });

                $(\' .expander \').click(function(e) {
                   $(\'#dialog\').html($(this).parent().parent().find(\'div.content\').html());
                   $(\'#dialog\').dialog( "open" );
                   e.preventDefault();
                });

                var container = document.querySelector(\'#container\');
                var msnry = new Masonry( container, {
                  // options
                  columnWidth: 50,
                  itemSelector: \'.widget\'
                 });
             });
        </script>
    </head>
    <body>
        <div id="dialog"></div>
        <div id="container">';
    $html .= $overall['widget'];
    $html .= $widgets;
    $html .= '
        </div>
    </body>
    </html>
    ';

    $filehanlder = fopen("analysis/analysis.html", 'w');
    fwrite($filehanlder, $html);


  }

}