<?php

namespace Kanboard\Helper;

use Kanboard\Core\Base;

/**
 * Project Header Helper
 *
 * @package helper
 * @author  Frederic Guillot
 */
class ProjectHeaderHelper extends Base
{
    /**
     * Get current search query
     *
     * @access public
     * @param  array  $project
     * @return string
     */
    public function getSearchQuery(array $project)
    {
        $search = $this->request->getStringParam('search', $this->userSession->getFilters($project['id']));
        $this->userSession->setFilters($project['id'], $search);
        return urldecode($search);
    }

    /**
     * Render project header (views switcher and search box)
     *
     * @access public
     * @param  array  $project
     * @param  string $controller
     * @param  string $action
     * @param  bool   $boardView
     * @param  string $plugin
     * @return string
     */
    public function render(array $project, $controller, $action, $boardView = false, $plugin = '')
    {
        $filters = array(
            'controller' => $controller,
            'action' => $action,
            'project_id' => $project['id'],
            'search' => $this->getSearchQuery($project),
            'plugin' => $plugin,
        );

        return $this->template->render('project_header/header', array(
            'project' => $project,
            'filters' => $filters,
            'categories_list' => $this->categoryModel->getList($project['id'], false),
            'tags_list' => $this->tagModel->getList($project['id'], false),
            'users_list' => $this->projectUserRoleModel->getAssignableUsersList($project['id'], false),
            'custom_filters_list' => $this->customFilterModel->getAll($project['id'], $this->userSession->getId()),
            'board_view' => $boardView,
        ));
    }

    /**
     * Get project description
     *
     * @access public
     * @param  array  &$project
     * @return string
     */
    public function getDescription(array &$project)
    {
        if ($project['owner_id'] > 0) {
            $description = t('Project owner: ').'**'.$this->helper->text->e($project['owner_name'] ?: $project['owner_username']).'**'.PHP_EOL.PHP_EOL;

            if (! empty($project['description'])) {
                $description .= '***'.PHP_EOL.PHP_EOL;
                $description .= $project['description'];
            }
        } else {
            $description = $project['description'];
        }

        return $description;
    }

    public function renderBurnTagField(array $project, array $tags = array())
    {
        $options = $this->tagModel->getAssignableList($project['id']);

        $tags = array();
        if ($project['burn_tags'] != null) {
            $tags = explode(',',$project['burn_tags']);
        }

        $html = $this->helper->form->label(t('Burn tags'), 'tags[]');
        $html .= '<input type="hidden" name="tags[]" value="">';
        $html .= '<select name="tags[]" id="form-tags" class="tag-autocomplete" multiple>';

        foreach ($options as $id => $tag) {
            $html .= sprintf(
                '<option value="%s" %s>%s</option>',
                $this->helper->text->e($id),
                in_array($id, $tags) ? 'selected="selected"' : '',
                $this->helper->text->e($tag)
            );
        }

        $html .= '</select>';

        return $html;
    }

    public function renderUnEvaTagField(array $project, array $tags = array())
    {
        $options = $this->tagModel->getAssignableList($project['id']);

        $tags = array();
        if ($project['not_eva_tags'] != null) {
            $tags = explode(',',$project['not_eva_tags']);
        }

        $html = $this->helper->form->label(t('Not evaluated tags'), 'eva_tags[]');
        $html .= '<input type="hidden" name="eva_tags[]" value="">';
        $html .= '<select name="eva_tags[]" id="form-tags" class="tag-autocomplete" multiple>';

        foreach ($options as $id => $tag) {
            $html .= sprintf(
                '<option value="%s" %s>%s</option>',
                $this->helper->text->e($id),
                in_array($id, $tags) ? 'selected="selected"' : '',
                $this->helper->text->e($tag)
            );
        }

        $html .= '</select>';

        return $html;
    }
}
