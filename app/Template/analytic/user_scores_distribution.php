<?php if (! $is_ajax): ?>
    <div class="page-header">
        <h2><?= t('User scores repartition') ?></h2>
    </div>
<?php endif ?>

<?php if (empty($metrics)): ?>
    <p class="alert"><?= t('Not enough data to show the graph.') ?></p>
<?php else: ?>
    <?= $this->app->component('chart-project-user-distribution', array(
        'metrics' => $metrics,
    )) ?>

    <table class="table-striped">
        <tr>
            <th><?= t('User') ?></th>
            <th><?= t('Number of scores') ?></th>
            <th><?= t('Number of done') ?></th>
            <th><?= t('Percentage of complete') ?></th>
            <th><?= t('Percentage of scores') ?></th>
        </tr>
        <?php foreach ($metrics as $metric): ?>
        <tr>
            <td>
                <?= $this->text->e($metric['user']) ?>
            </td>
            <td>
                <?= $metric['nb_scores'] ?>
            </td>
            <td>
                <?= $metric['nb_done_scores'] ?>
            </td>
            <td>
                <?= $metric['complete_percentage'] ?>
            </td>
            <td>
                <?= n($metric['scores_percentage']) ?>%
            </td>
        </tr>
        <?php endforeach ?>
    </table>
<?php endif ?>
