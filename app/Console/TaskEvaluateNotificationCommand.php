<?php

namespace Kanboard\Console;

use Kanboard\Model\TaskScoreModel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TaskEvaluateNotificationCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('notification:evaluate-tasks')
            ->setDescription('Send notifications for evaluation')
            ->addOption('score', 's', InputOption::VALUE_REQUIRED, 'Send all unvalued tasks to users in one email')
            ->addOption('user','u',InputOption::VALUE_OPTIONAL,'Send all unvalued tasks to specified user in one email')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('score')) {
            $project_id = $input->getOption('score');
        }

        $user_id = 0;
        if ($input->getOption('user')) {
            $user_id = $input->getOption('user');
        }

        $tasks = $this->taskFinderModel->getEvaTasksByProject($project_id,'');
        $tasks = $this->sendEvaTaskNotifications($tasks,$user_id);
    }

    public function sendEvaTaskNotifications(array $tasks, $user_id)
    {
        if ($user_id > 0) {
            $users = $this->userModel->getUsertoNotification($user_id);
        }

        foreach ($this->groupByColumn($tasks, 'project_id') as $project_id => $project_tasks) {
            if ($user_id <= 0) {
                $users = $this->userNotificationModel->getUsersWithNotificationEnabled($project_id);
            }

            foreach ($users as $user) {
                //$role = $this->projectUserRoleModel->getUserRole($project_id, $user['id']);
                $this->sendUserEvaTaskNotifications($user, $project_tasks);
            }
        }

        return $tasks;
    }

    public function sendUserEvaTaskNotifications(array $user, array $tasks)
    {
        $user_tasks = array();
        $project_names = array();

        if ($user['sub_role'] == "") {
            return true;
        }

        foreach ($tasks as $task) {
            if ($this->taskScoreModel->shouldReceiveNotification($user, $task)) {
                $user_tasks[] = $task;
                $project_names[$task['project_id']] = $task['project_name'];
            }
        }

        if (! empty($user_tasks)) {
            $this->userNotificationModel->sendUserNotification(
                $user,
                TaskScoreModel::EVENT_EVALUATE,
                array('tasks' => $user_tasks, 'project_name' => implode(', ', $project_names),'task_count' => count($user_tasks))
            );
        }

        return true;
    }

    /**
     * Group a collection of records by a column
     *
     * @access public
     * @param  array   $collection
     * @param  string  $column
     * @return array
     */
    public function groupByColumn(array $collection, $column)
    {
        $result = array();

        foreach ($collection as $item) {
            $result[$item[$column]][] = $item;
        }

        return $result;
    }
}
