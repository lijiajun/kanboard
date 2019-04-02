<h2><?= t('Unevaluated tasks for the "%s" project(%d)', $project_name, $task_count) ?></h2>

<table style="font-size: .8em; table-layout: fixed; width: 100%; border-collapse: collapse; border-spacing: 0; margin-bottom: 20px;" cellpadding=5 cellspacing=1>
    <tr style="background: #fbfbfb; text-align: left; padding-top: .5em; padding-bottom: .5em; padding-left: 3px; padding-right: 3px;">
        <th style="border: 1px solid #eee;"><?= t('Id') ?></th>
        <th style="border: 1px solid #eee;"><?= t('Title') ?></th>
        <th style="border: 1px solid #eee;"><?= t('Swimlanes') ?></th>
        <th style="border: 1px solid #eee;"><?= t('Category') ?></th>
        <th style="border: 1px solid #eee;"><?= t('Start Date') ?></th>
        <th style="border: 1px solid #eee;"><?= t('Completion date') ?></th>
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
            <td style="border: 1px solid #eee;"><?= $this->text->e($task['swimlane_name']) ?></td>
            <td style="border: 1px solid #eee;"><?= $this->text->e($task['category_name']) ?></td>
            <td style="border: 1px solid #eee;">
                <?php if ($task['date_started'] === null or $task['date_started'] == 0): ?>
                    <?= $this->dt->datetime($task['date_creation']) ?>
                <?php else: ?>
                    <?= $this->dt->datetime($task['date_started']) ?>
                <?php endif ?>
            </td>
            <td style="border: 1px solid #eee;">
                <?php if ($task['date_completed'] === null or $task['date_completed'] == 0): ?>
                    <?= $this->text->e($task['column_name']) ?>
                <?php else: ?>
                    <?= $this->dt->datetime($task['date_completed']) ?>
                <?php endif ?>
            </td>
        </tr>
    <?php endforeach ?>
</table>

<?php if ($this->app->config('application_url') != ''): ?>
    <?= $this->url->absoluteLink(t('Click this link to evaluate on Kanboard'), 'TaskScoreController', 'show', array('project_id' => $task['project_id'])) ?>
<?php endif ?>