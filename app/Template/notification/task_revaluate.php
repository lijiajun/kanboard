<h2><?= t('There is a task under the "%s" project that needs to be reassessed', $project_name) ?></h2>

<table style="font-size: .8em; table-layout: fixed; width: 100%; border-collapse: collapse; border-spacing: 0; margin-bottom: 20px;" cellpadding=5 cellspacing=1>
    <tr style="background: #fbfbfb; text-align: left; padding-top: .5em; padding-bottom: .5em; padding-left: 3px; padding-right: 3px;">
        <th style="border: 1px solid #eee;"><?= t('Id') ?></th>
        <th style="border: 1px solid #eee;"><?= t('Title') ?></th>
        <th style="border: 1px solid #eee;"><?= t('Assignee') ?></th>
        <th style="border: 1px solid #eee;"><?= t('Last score') ?></th>
    </tr>

    <tr style="overflow: hidden; background: #fff; text-align: left; padding-top: .5em; padding-bottom: .5em; padding-left: 3px; padding-right: 3px;">
        <td style="border: 1px solid #eee;">#<?= $task['id'] ?></td>
        <td style="border: 1px solid #eee;">
            <?= $this->text->e($task['title']) ?>
        </td>
        <td style="border: 1px solid #eee;">
            <?php if (! empty($task['assignee_username'])): ?>
                <?= $this->text->e($task['assignee_name'] ?: $task['assignee_username']) ?>
            <?php endif ?>
        </td>
        <td style="border: 1px solid #eee;"><?= $score / 10 ?></td>
    </tr>
</table>

<?php if ($this->app->config('application_url') != ''): ?>
    <?= $this->url->absoluteLink(t('Click this link to evaluate on Kanboard'), 'TaskScoreController', 'show', array('project_id' => $task['project_id'])) ?>
<?php endif ?>