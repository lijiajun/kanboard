<h2><?= t('"%s" project has a task to complete the assessment', $task['project_name']) ?></h2>

<table style="font-size: .8em; table-layout: fixed; width: 100%; border-collapse: collapse; border-spacing: 0; margin-bottom: 20px;" cellpadding=5 cellspacing=1>
    <tr style="background: #fbfbfb; text-align: left; padding-top: .5em; padding-bottom: .5em; padding-left: 3px; padding-right: 3px;">
        <th style="border: 1px solid #eee;"><?= t('Id') ?></th>
        <th style="border: 1px solid #eee;"><?= t('Title') ?></th>
        <th style="border: 1px solid #eee;"><?= t('Category') ?></th>
        <th style="border: 1px solid #eee;"><?= t('Evaluation result') ?></th>
    </tr>

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
        <td style="border: 1px solid #eee;"><?= $this->text->e($task['score']) ?></td>
    </tr>
</table>

<?= $this->render('notification/footer', array('task' => $task)) ?>