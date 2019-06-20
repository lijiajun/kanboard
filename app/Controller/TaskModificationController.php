<?php

namespace Kanboard\Controller;

use Kanboard\Core\Controller\AccessForbiddenException;
use Kanboard\Core\ExternalTask\AccessForbiddenException as ExternalTaskAccessForbiddenException;
use Kanboard\Core\ExternalTask\ExternalTaskException;

/**
 * Task Modification controller
 *
 * @package  Kanboard\Controller
 * @author   Frederic Guillot
 */
class TaskModificationController extends BaseController
{
    public function assignToMe()
    {
        $task = $this->getTask();
        $values = ['id' => $task['id'], 'owner_id' => $this->userSession->getId()];

        if (! $this->helper->projectRole->canUpdateTask($task)) {
            throw new AccessForbiddenException(t('You are not allowed to update tasks assigned to someone else.'));
        }
        
        if (! $this->helper->projectRole->canChangeAssignee($task)) {
            throw new AccessForbiddenException(t('You are not allowed to change the assignee.'));
        }

        $this->taskModificationModel->update($values);
        $this->redirectAfterQuickAction($task);
    }

    /**
     * Set the start date automatically
     *
     * @access public
     */
    public function start()
    {
        $task = $this->getTask();
        $values = ['id' => $task['id'], 'date_started' => time()];

        if (! $this->helper->projectRole->canUpdateTask($task)) {
            throw new AccessForbiddenException(t('You are not allowed to update tasks assigned to someone else.'));
        }

        $this->taskModificationModel->update($values);
        $this->redirectAfterQuickAction($task);
    }

    protected function redirectAfterQuickAction(array $task)
    {
        switch ($this->request->getStringParam('redirect')) {
            case 'board':
                $this->response->redirect($this->helper->url->to('BoardViewController', 'show', ['project_id' => $task['project_id']]));
                break;
            case 'list':
                $this->response->redirect($this->helper->url->to('TaskListController', 'show', ['project_id' => $task['project_id']]));
                break;
            case 'dashboard':
                $this->response->redirect($this->helper->url->to('DashboardController', 'show', [], 'project-tasks-'.$task['project_id']));
                break;
            case 'dashboard-tasks':
                $this->response->redirect($this->helper->url->to('DashboardController', 'tasks', ['user_id' => $this->userSession->getId()]));
                break;
            default:
                $this->response->redirect($this->helper->url->to('TaskViewController', 'show', ['project_id' => $task['project_id'], 'task_id' => $task['id']]));
        }
    }

    /**
     * Display a form to edit a task
     *
     * @access public
     * @param array $values
     * @param array $errors
     * @throws \Kanboard\Core\Controller\AccessForbiddenException
     * @throws \Kanboard\Core\Controller\PageNotFoundException
     */
    public function edit(array $values = array(), array $errors = array())
    {
        $task = $this->getTask();
        $score_list = array();

        if (!$this->helper->projectRole->canUpdateTask($task)) {
            throw new AccessForbiddenException(t('You are not allowed to update tasks assigned to someone else.'));
        }

        $project = $this->projectModel->getById($task['project_id']);
        $task_tags = $this->taskTagModel->getList($task['id']);
        $not_eva_tags = $this->projectModel->getNotEvaTags($task['project_id']);
        foreach ($not_eva_tags as $key => $tag_id) {
            foreach ($task_tags as $task_tag_id => $task_tag_name) {
                if ($tag_id == $task_tag_id) {
                    $score_list = array("0" => "Zero(0点)",
                        "5" => "XXS(½点)",
                        "10" => "XS(1点)",
                        "20" => "S(2点)",
                        "30" => "M(3点)",
                        "50" => "L(5点)",
                        "80" => "XL(8点)",
                        "130" => "XXL(13点)",
                        "200" => "XXXL(20点)");
                    break;
                }
            }
            if (count($score_list) > 0) {
                break;
            }
        }


        if (empty($values)) {
            $values = $task;
        }

        $values = $this->hook->merge('controller:task:form:default', $values, array('default_values' => $values));
        $values = $this->hook->merge('controller:task-modification:form:default', $values, array('default_values' => $values));

        $params = array(
            'project' => $project,
            'values' => $values,
            'errors' => $errors,
            'task' => $task,
            'tags' => $this->taskTagModel->getList($task['id']),
            'users_list' => $this->projectUserRoleModel->getAssignableUsersList($task['project_id']),
            'categories_list' => $this->categoryModel->getList($task['project_id']),
            'score_list' => $score_list,
            'columns_list' => $this->columnModel->getList($project['id']),
            'swimlanes_list' => $this->swimlaneModel->getList($project['id'], false, true),
        );

        $this->renderTemplate($task, $params);
    }

    protected function renderTemplate(array &$task, array &$params)
    {
        if (empty($task['external_uri'])) {
            $this->response->html($this->template->render('task_modification/show', $params));
        } else {

            try {
                $taskProvider = $this->externalTaskManager->getProvider($task['external_provider']);
                $params['template'] = $taskProvider->getModificationFormTemplate();
                $params['external_task'] = $taskProvider->fetch($task['external_uri']);
            } catch (ExternalTaskAccessForbiddenException $e) {
                throw new AccessForbiddenException($e->getMessage());
            } catch (ExternalTaskException $e) {
                $params['error_message'] = $e->getMessage();
            }

            $this->response->html($this->template->render('external_task_modification/show', $params));
        }
    }

    /**
     * Validate and update a task
     *
     * @access public
     */
    public function update()
    {
        $task = $this->getTask();
        $values = $this->request->getValues();
        $values['id'] = $task['id'];
        $values['project_id'] = $task['project_id'];

        list($valid, $errors) = $this->taskValidator->validateModification($values);

        if ($valid && $this->updateTask($task, $values, $errors)) {
            $this->flash->success(t('Task updated successfully.'));
            $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array('project_id' => $task['project_id'], 'task_id' => $task['id'])), true);
        } else {
            $this->flash->failure(t('Unable to update your task.'));
            $this->edit($values, $errors);
        }
    }

    protected function updateTask(array &$task, array &$values, array &$errors)
    {
        if (isset($values['owner_id']) && $values['owner_id'] != $task['owner_id'] && !$this->helper->projectRole->canChangeAssignee($task)) {
            throw new AccessForbiddenException(t('You are not allowed to change the assignee.'));
        }

        if (!$this->helper->projectRole->canUpdateTask($task)) {
            throw new AccessForbiddenException(t('You are not allowed to update tasks assigned to someone else.'));
        }

        $result = $this->taskModificationModel->update($values);

        if ($result && !empty($task['external_uri'])) {
            try {
                $taskProvider = $this->externalTaskManager->getProvider($task['external_provider']);
                $result = $taskProvider->save($task['external_uri'], $values, $errors);
            } catch (ExternalTaskAccessForbiddenException $e) {
                throw new AccessForbiddenException($e->getMessage());
            } catch (ExternalTaskException $e) {
                $this->logger->error($e->getMessage());
                $result = false;
            }
        }

        return $result;
    }
}
