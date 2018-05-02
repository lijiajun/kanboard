<?php

namespace Kanboard\Controller;

use Kanboard\Core\Controller\AccessForbiddenException;
use Kanboard\Core\Controller\PageNotFoundException;
use Kanboard\Model\TaskModel;
use Kanboard\Model\ColumnModel;
use Kanboard\Model\SwimlaneModel;
use Kanboard\Model\CategoryModel;
use Kanboard\Model\ProjectModel;
use Kanboard\Model\UserModel;
use Kanboard\Core\Security\Role;

/**
 * Task Controller
 *
 * @package  Kanboard\Controller
 * @author   Frederic Guillot
 */
class TaskScoreController extends BaseController
{
    public function show(array $values = array(), array $errors = array())
    {
        $showTasks = array();
        $remark = e('No tasks to evaluate');
        $project = $this->getProject();
        $user = $this->getUser();
        $role = $this->projectUserRoleModel->getUserRole($project['id'], $user['id']);
        if ($role == Role::PROJECT_VIEWER) {
            $remark = e('You are an observer of the project and cannot participate in the complexity assessment.');
        } elseif ($role == Role::PROJECT_EXT_MEMBER) {
            $remark = e('You are a ext_member of the project and cannot participate in the complexity assessment.');
        } elseif ($user['sub_role'] == "") {
            $remark = e('You are not assigned sub-role and cannot participate in complexity evaluation.');
        } else {
            $tasks = $this->taskFinderModel->getEvaTasksByProject($project['id'],$user['id']);
            foreach ($tasks as $task) {
                if ($user['sub_role'] == $task['sub_role'])
                    $showTasks[] = $task;
            }
        }

        $score_list = array(null =>e('Please choose'),
            "0" => "Zero(0点)",
            "5" => "XXS(½点)",
            "10" => "XS(1点)",
            "20" => "S(2点)",
            "30" => "M(3点)",
            "50" => "L(5点)",
            "80" => "XL(8点)",
            "130" => "XXL(13点)",
            "200" => "XXXL(20点)");

        $this->response->html($this->helper->layout->app('task/score', array(
            'project' => $project,
            'tasks' => $showTasks,
            'taskCount' => count($showTasks),
            'score_list' => $score_list,
            'errors' => $errors,
            'values' => $values,
            'title' => $project['name'],
            'remark' => $remark,
            'description' => $this->helper->projectHeader->getDescription($project),
            'board_private_refresh_interval' => $this->configModel->get('board_private_refresh_interval'),
            'board_highlight_period' => $this->configModel->get('board_highlight_period'),
        )));
    }

    public function save()
    {
        $project = $this->getProject();
        $values = $this->request->getValues();
        $user = $this->getUser();

        if (empty($values) || $user == '') {
            $this->flash->failure(t('Task is evaluated failed.'));
            $this->response->redirect($this->helper->url->to('TaskScoreController', 'show', array('project_id' => $project['id'])), true);
        } else {
            $evaCount = $this->taskScoreModel->insertTaskScore($user,$values,$project['id']);
            if ($evaCount > 0) {
                $this->flash->success(t('%d tasks are evaluated successfully.',$evaCount));
            } else {
                $this->flash->failure(t('Task is evaluated failed.'));
            }
            $this->response->redirect($this->helper->url->to('TaskScoreController', 'show', array('project_id' => $project['id'])), true);
        }
    }
}
