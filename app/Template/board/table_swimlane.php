<!-- swimlane -->
<tr id="swimlane-<?= $swimlane['id'] ?>">
   <th class="board-swimlane-header" colspan="<?= $swimlane['nb_columns'] ?>">
        <?php if (! $not_editable): ?>
            <a href="#" class="board-swimlane-toggle" data-swimlane-id="<?= $swimlane['id'] ?>">
                <?php if ($swimlane['nb_tasks'] > 0): ?>
                    <i class="fa fa-chevron-circle-down hide-icon-swimlane-<?= $swimlane['id'] ?>" title="<?= t('Collapse swimlane') ?>"></i>
                    <i class="fa fa-chevron-circle-right show-icon-swimlane-<?= $swimlane['id'] ?>" title="<?= t('Expand swimlane') ?>" style="display: none"></i>
                <?php else: ?>
                    <i class="fa fa-chevron-circle-down hide-icon-swimlane-<?= $swimlane['id'] ?>" title="<?= t('Collapse swimlane') ?>" style="display: none"></i>
                    <i class="fa fa-chevron-circle-right show-icon-swimlane-<?= $swimlane['id'] ?>" title="<?= t('Expand swimlane') ?>"></i>
                <?php endif ?>
            </a>
        <?php endif ?>

        <?= $this->text->e($swimlane['name']) ?>

        <?php if (! $not_editable && ! empty($swimlane['description'])): ?>
            <?= $this->app->tooltipLink('<i class="fa fa-info-circle"></i>', $this->url->href('BoardTooltipController', 'swimlane', array('swimlane_id' => $swimlane['id'], 'project_id' => $project['id']))) ?>
        <?php endif ?>

        <span title="<?= t('Task count') ?>" class="board-column-header-task-count swimlane-task-count-<?= $swimlane['id'] ?>">
            (<?= $swimlane['nb_tasks'] ?>)
        </span>
    </th>
</tr>
