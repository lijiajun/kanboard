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
        $total = 0;
        $tasks = $this->taskFinderModel->getAllSort($project_id, 'owner_id');
        $users = $this->projectUserRoleModel->getAssignableUsersList($project_id);
        $first_column = $this->columnModel->getFirstColumnId($project_id);
        $last_column = $this->columnModel->getLastColumnId($project_id);

        foreach ($tasks as $task) {
            $user = isset($users[$task['owner_id']]) ? $users[$task['owner_id']] : $users[0];
            $total++;

            if (! isset($metrics[$user])) {
                $metrics[$user] = array(
                    'nb_todo_tasks' => 0,
                    'nb_doing_tasks' => 0,
                    'nb_done_tasks' => 0,
                    'nb_tasks' => 0,
                    'percentage' => 0,
                    'user' => $user,
                );
            }

            if ($task['column_id'] == $first_column) {
                $metrics[$user]['nb_todo_tasks']++;
            } elseif ($task['column_id'] == $last_column) {
                $metrics[$user]['nb_done_tasks']++;
            } else {
                $metrics[$user]['nb_doing_tasks']++;
            }

            $metrics[$user]['nb_tasks']++;
        }

        if ($total === 0) {
            return array();
        }

        foreach ($metrics as &$metric) {
            $metric['percentage'] = round(($metric['nb_tasks'] * 100) / $total, 2);
        }

        //ksort($metrics);

        return array_values($metrics);
    }
}
