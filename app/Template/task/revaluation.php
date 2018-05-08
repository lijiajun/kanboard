<div class="page-header">
    <h2><?= $this->text->e($task['title']) ?></h2>
</div>

<p class="alert"><?= t('The assessment record for this task(#%d) has been cleared. Please reassess.', $task['id']) ?></p>

