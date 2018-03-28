<div class="page-header">
    <h2><?= $this->text->e($project['name']) ?> &gt; <?= t('Complexity assessment(%d tasks)', $taskCount) ?></h2>
</div>

<?php if ($taskCount > 0): ?>
    <form method="post" action="<?= $this->url->href('TaskScoreController', 'save', array('project_id' => $project['id'])) ?>" autocomplete="off">
        <?= $this->form->csrf() ?>
        <table style="font-size: .8em; table-layout: fixed; width: 100%; border-collapse: collapse; border-spacing: 0; margin-bottom: 20px;" cellpadding=5 cellspacing=1>
            <tr style="background: #fbfbfb; text-align: left; padding-top: .5em; padding-bottom: .5em; padding-left: 3px; padding-right: 3px;">
                <th style="border: 1px solid #eee;"><?= t('Id') ?></th>
                <th style="border: 1px solid #eee;"><?= t('Title') ?></th>
                <th style="border: 1px solid #eee;"><?= t('Category') ?></th>
                <th style="border: 1px solid #eee;"><?= t('Assignee') ?></th>
                <th style="border: 1px solid #eee;"><?= t('Create date') ?></th>
                <th style="border: 1px solid #eee;"><?= t('Score') ?></th>
            </tr>

            <?php foreach ($tasks as $task): ?>
                <tr style="overflow: hidden; background: #fff; text-align: left; padding-top: .5em; padding-bottom: .5em; padding-left: 3px; padding-right: 3px;">
                    <td style="border: 1px solid #eee;">#<?= $task['id'] ?></td>
                    <td style="border: 1px solid #eee;">
                        <?php if (! empty($application_url)): ?>
                            <?= $this->url->absoluteLink($this->text->e($task['title']), 'TaskViewController', 'show', array('task_id' => $task['id'], 'project_id' => $task['project_id'])) ?>
                        <?php else: ?>
                            <?= $this->text->e($task['title']) ?>
                        <?php endif ?>
                    </td>
                    <td style="border: 1px solid #eee;"><?= $this->text->e($task['category_name']) ?></td>
                    <td style="border: 1px solid #eee;">
                        <?php if (! empty($task['assignee_username'])): ?>
                            <?= $this->text->e($task['assignee_name'] ?: $task['assignee_username']) ?>
                        <?php endif ?>
                    </td>
                    <td style="border: 1px solid #eee;"><?= $this->dt->datetime($task['date_creation']) ?></td>
                    <td style="border: 1px solid #eee;">
                        <div class="task-form-secondary-column">
                            <?= $this->task->helper->form->select($task['id'], $score_list, $values, $errors, array()) ?>
                            <?= $this->hook->render('template:task:form:second-column', array('values' => $values, 'errors' => $errors)) ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach ?>
        </table>
        <div class="task-form-bottom">
            <?= $this->modal->submitButtons() ?>
        </div>
     </form>
<?php elseif (isset($remark)): ?>
    <p class="alert"><?= $remark ?></p>
<?php else: ?>
    <p class="alert"><?= t('No tasks to evaluate') ?></p>
<?php endif ?>
