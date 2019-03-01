<?php

namespace Kanboard\Analytic;

use Kanboard\Core\Base;

/**
 * User Distribution
 *
 * @package  analytic
 * @author   Frederic Guillot
 */
class UserDistributionAnalytic extends Base
{
    /**
     * Build Report
     *
     * @access public
     * @param  integer   $project_id
     * @return array
     */
    public function build($project_id)
    {
        $metrics = array();
        $metricGroup = array();
        $totalScores = array();
        $tasks = $this->taskFinderModel->getAllSort($project_id, 'owner_id');
        $users = $this->projectUserRoleModel->getAssignableUsersList($project_id, true, false,false,true);
        $first_column = $this->columnModel->getFirstColumnId($project_id);
        $last_column = $this->columnModel->getLastColumnId($project_id);

        foreach ($users as $user) {
            if (!isset($totalScores[$user[1]]['score'])) {
                $totalScores[$user[1]]['score'] = 0;
                $totalScores[$user[1]]['count'] = 0;
            }
        }

        foreach ($tasks as $task) {
            $userInfo = isset($users[$task['owner_id']]) ? $users[$task['owner_id']] : $users[0];
            $userName = $userInfo[0];
            $userRole = $userInfo[1];
            //$totalScores += (int)$task['score'];
            $totalScores[$userRole]['score'] += (int)$task['score'];
            $totalScores[$userRole]['count']++;

            if (! isset($metricGroup[$userRole][$userName])) {
                $metricGroup[$userRole][$userName] = array(
                    'nb_todo_tasks' => 0,
                    'nb_doing_tasks' => 0,
                    'nb_done_tasks' => 0,
                    'nb_done_scores' => 0,
                    'nb_tasks' => 0,
                    'nb_scores' => 0,
                    'percentage' => 0,
                    'user' => $userName,
                    'role' => $userRole,
                );
            }

            if ($task['column_id'] == $first_column) {
                $metricGroup[$userRole][$userName]['nb_todo_tasks']++;
            } elseif ($task['column_id'] == $last_column) {
                $metricGroup[$userRole][$userName]['nb_done_tasks']++;
                $metricGroup[$userRole][$userName]['nb_done_scores'] += (float)$task['score'] / 10;
            } else {
                $metricGroup[$userRole][$userName]['nb_doing_tasks']++;
            }

            $metricGroup[$userRole][$userName]['nb_tasks']++;
            $metricGroup[$userRole][$userName]['nb_scores'] += (float)$task['score'] / 10;
        }

        $result = array();
        foreach ($metricGroup as $metrics) {
            foreach ($metrics as &$metric) {
                $metric['tasks_percentage'] = round(($metric['nb_tasks'] * 100) / $totalScores[$metric['role']]['count'], 2);
                if ($metric['nb_scores'] > 0) {
                    $metric['complete_percentage'] = strval(round(($metric['nb_done_scores'] * 1000) / ($metric['nb_scores'] * 10), 2)) . '%';
                } else {
                    $metric['complete_percentage'] = '0%';
                }

                if ($totalScores[$metric['role']]['score'] > 0) {
                    $metric['scores_percentage'] = round(($metric['nb_scores'] * 1000) / $totalScores[$metric['role']]['score'], 2);
                } else {
                    $metric['scores_percentage'] = '0%';
                }
            }

            $nbDoneScores = array_column($metrics, 'nb_done_scores');
            array_multisort($nbDoneScores, SORT_DESC, $metrics);
            $result += $metrics;
        }

        if (count($result) === 0) {
            return array();
        }

        return array_values($result);
    }
}
