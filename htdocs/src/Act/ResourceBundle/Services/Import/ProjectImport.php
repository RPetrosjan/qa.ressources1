<?php

namespace Act\ResourceBundle\Services\Import;

use Act\ResourceBundle\Entity\CommonTask;
use Act\ResourceBundle\Entity\Project;
use Act\ResourceBundle\Entity\MetaTask;
use Act\ResourceBundle\Entity\SubTask;
use Symfony\Component\Translation\TranslatorInterface;

class ProjectImport extends Import {
  protected $translator;

  public function setTranslator(TranslatorInterface $translator) {
    $this->translator = $translator;
  }

  /**
   * Parse the Excel file and retrieve project and tasks
   *
   * @param $filename
   * @return array
   * @throws \Exception
   */
  public function import($filename) {
    $imported = array('new' => array(), 'updated' => array());

    $excelObj = $this->excel->createPHPExcelObject($filename);
    $excelObj->setActiveSheetIndex(0);

    // Get the project name
    $projectName = $excelObj->getActiveSheet()
      ->getCellByColumnAndRow(0, 2)
      ->getValue();
    if (strlen($projectName) == 0) {
      throw new \Exception('Project name not found');
    }

    $project = $this->em->getRepository('ActResourceBundle:Project')
      ->findOneBy(array('name' => $projectName));
    if (!$project) {
      // If no project found, create a new one
      $project = new Project();
      $project->setName($projectName);
      $project->generateNameShort();
      $this->em->persist($project);
      $imported[] = $project;
    }

    // Get all rows as array
    $data = $this->getData($filename, 3);

    // Equipes, Profils verification
    $errorTeamsProfiles = array();
    $errorLine = array();
    foreach ($data as $i => $row) {
      if (isset($row['teams_profiles'])) {
        $teams = array_unique(explode(';', $row['teams_profiles']));
        foreach ($teams as $t) {
          $t = trim($t);
          $team = $this->em->getRepository('ActResourceBundle:Team')
            ->findOneBy(array('name' => $t));

          if (!$team) {
            $profile = $this->em->getRepository('ActResourceBundle:TeamProfile')
              ->findOneBy(array('name' => $t));
            if (!$profile) {
              $errorTeamsProfiles[] = $t;
              $errorLine[] = $i;
            }
          }
        }
      }
    }

    $j = 0;
    if (count($errorTeamsProfiles) > 0) {
      // one or more team/profile does not exist
      $message = '';
      foreach ($errorTeamsProfiles as $te) {
        // Excel line number
        $line = $errorLine[$j++] + 1;
        $message .= $this->translator->trans('team.or.profile.not.exist', array(
            '%te%' => $te,
            '%line%' => $line
          )) . '<br/>';
      }

      throw new \Exception($message);
    }

    $prevMetatask = NULL;
    $prevCommontask = NULL;

    foreach ($data as $i => $row) {
      $task = NULL;

      // Count number of spaces at the beginning of the task name
      $spaces = strspn($row['task_name'], ' ');
      $taskName = trim($row['task_name']);

      switch ($spaces) {
        case 3:
          if ($prevMetatask == NULL) {
            throw new \Exception('La tâche "' . $taskName . '" n\'a pas de tâche parente définie.');
          }

          // Try to load an existing commontask
          $found = FALSE;
          foreach ($prevMetatask->getCommontasks() as $c) {
            if ($c->getName() == $taskName) {
              $task = $c;
              $imported['updated'][] = '--- ' . $taskName;
              $found = TRUE;
            }
          }

          if (!$found) {
            $task = new CommonTask();
            $imported['new'][] = '--- ' . $taskName;
          }

          $task->setMetatask($prevMetatask);
          $prevCommontask = $task;
          break;

        case 6:
          // Try to load an existing subtask
          $found = FALSE;
          foreach ($prevCommontask->getSubtasks() as $s) {
            if ($s->getName() == $taskName) {
              $task = $s;
              $imported['updated'][] = '------ ' . $taskName;
              $found = TRUE;
            }
          }

          if (!$found) {
            $task = new SubTask();
            $imported['new'][] = '------ ' . $taskName;
          }

          $task->setCommontask($prevCommontask);
          break;

        default:
          // Try to load an existing metatask
          $found = FALSE;
          foreach ($project->getMetaTasks() as $m) {
            if ($m->getName() == $taskName) {
              $task = $m;
              $imported['updated'][] = $taskName;
              $found = TRUE;
            }
          }

          if (!$found) {
            $task = new MetaTask();
            $imported['new'][] = $taskName;
          }

          $prevMetatask = $task;
          break;
      }

      $task->setProject($project);
      $task->setName($taskName);
      $task->setStart($row['start']);
      $task->setEnd($row['end']);
      if (isset($row['workload'])) {
        $task->setWorkloadSold($row['workload']);
      }

      // Add teams/profiles
      $task->clearTeamsAndProfiles();
      $teams = array_unique(explode(';', $row['teams_profiles']));


      foreach ($teams as $t) {
        $t = trim($t);
        $team = $this->em->getRepository('ActResourceBundle:Team')
          ->findOneBy(array('name' => $t));
        if ($team) {
          $task->addTeam($team);
        }

        $profile = $this->em->getRepository('ActResourceBundle:TeamProfile')
          ->findOneBy(array('name' => $t));
        if ($profile) {
          $task->addTeamprofile($profile);
        }
      }

      $this->em->persist($task);
    }

    $this->em->flush();

    return $imported;
  }

  protected function getRequiredColumns() {
    return array(
      'task_name' => array('nom de la tâche', 'task name'),
      'start' => 'début',
      'end' => 'fin',
      'workload' => array('charge de travail vendu', 'workload'),
      'teams_profiles' => array('noms ressources', 'equipes/profils')
    );
  }

  protected function getTypes() {
    return array(
      'task_name' => 'string',
      'start' => 'date',
      'end' => 'date',
      'workload' => 'float',
      'teams_profiles' => 'string'
    );
  }
}