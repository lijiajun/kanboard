<?php if ($is_ajax): ?>
    <div class="page-header">
        <h2><?= $title ?></h2>
    </div>
<?php elseif(!isset($no_layout) || $no_layout == false): ?>
    <?= $this->projectHeader->render($project, 'TaskListController', 'show') ?>
<?php endif ?>
<section class="sidebar-container">
    <?php if (!isset($no_layout) || $no_layout == false): ?>
        <?= $this->render($sidebar_template, array('project' => $project)) ?>
    <?php endif ?>
    <div class="sidebar-content">
        <?= $content_for_sublayout ?>
    </div>
</section>
