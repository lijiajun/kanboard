<div class="page-header">
    <ul>
        <li><?= $this->url->icon('user', t('All users'), 'UserListController', 'show') ?></li>
    </ul>
</div>


<div class="filter-box margin-bottom">
    <form method="get" action="<?= $this->url->dir() ?>" class="search">
        <?= $this->form->hidden('controller', $values) ?>
        <?= $this->form->hidden('action', $values) ?>
        <div class="input-addon">
            <?= $this->form->text('search', $values, array(), array(empty($values['search']) ? 'autofocus' : '', 'placeholder="'.t('Please search by user').'"'), 'input-addon-field') ?>
        </div>
    </form>
</div>


<?php if (empty($values['search'])): ?>
    <div class="panel">
        <p class="alert"><?= t('The username is required') ?></p>
    </div>
<?php elseif (! empty($values['search']) && $paginator->isEmpty()): ?>
    <p class="alert"><?= t('Nothing found.') ?></p>
<?php elseif (! $paginator->isEmpty()): ?>
    <div class="table-list">
        <?= $this->render('user_list/header', array('paginator' => $paginator)) ?>
        <?php foreach ($paginator->getCollection() as $user): ?>
            <div class="table-list-row table-border-left">
                <?= $this->render('user_list/user_title', array(
                    'user' => $user,
                )) ?>

                <?= $this->render('user_list/user_details', array(
                    'user' => $user,
                )) ?>

                <?= $this->render('user_list/user_icons', array(
                    'user' => $user,
                )) ?>
            </div>
        <?php endforeach ?>
    </div>
     <?= $paginator ?>
<?php endif ?>